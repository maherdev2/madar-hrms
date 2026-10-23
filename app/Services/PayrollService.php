<?php
namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function calculate(User $actor, int $year, int $month): PayrollRun
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $end = $start->endOfMonth();
        return DB::transaction(function () use ($actor, $year, $month, $start, $end) {
            $period = PayrollPeriod::firstOrCreate(['year'=>$year,'month'=>$month], [
                'starts_on'=>$start->toDateString(), 'ends_on'=>$end->toDateString(), 'status'=>'open',
            ]);
            $period = PayrollPeriod::whereKey($period->id)->lockForUpdate()->first();
            if ($period->status === 'locked') throw ValidationException::withMessages(['month'=>'هذه الدورة معتمدة ومقفلة.']);
            $revision = ($period->runs()->max('revision') ?? 0) + 1;
            $run = $period->runs()->create(['revision'=>$revision,'status'=>'calculated','created_by_user_id'=>$actor->id]);
            $workDates = [];
            for ($day=$start; $day->lte($end); $day=$day->addDay()) if ($day->isWeekday()) $workDates[]=$day->toDateString();
            $workDays = count($workDates);

            Employee::with(['compensations','payComponents','attendanceLogs'=>fn($q)=>$q->whereBetween('work_date',[$start->toDateString(),$end->toDateString()]),
                'leaveRequests'=>fn($q)=>$q->where('status','approved')->whereDate('starts_on','<=',$end->toDateString())->whereDate('ends_on','>=',$start->toDateString())->with('type')])
                ->where('employment_status','active')->chunk(100, function ($employees) use ($run,$start,$end,$workDates,$workDays) {
                    foreach ($employees as $employee) {
                        $comp = $employee->compensations->filter(fn($c)=>$c->effective_from->toDateString() <= $end->toDateString() && (!$c->effective_to || $c->effective_to->toDateString() >= $start->toDateString()))->sortByDesc('effective_from')->first();
                        if (!$comp) continue;
                        $unpaid = [];
                        foreach ($employee->leaveRequests as $leave) {
                            if ($leave->type?->is_paid) continue;
                            foreach ($workDates as $date) if ($date >= $leave->starts_on->toDateString() && $date <= $leave->ends_on->toDateString()) $unpaid[$date]=true;
                        }
                        foreach ($employee->attendanceLogs as $log) if ($log->status === 'absent') $unpaid[$log->work_date->toDateString()]=true;
                        $unpaidDays = count($unpaid);
                        $payableDays = max(0, $workDays-$unpaidDays);
                        $workedHours = round((float)$employee->attendanceLogs->sum('worked_hours'),2);
                        $rate = (float)$comp->base_rate;
                        $base = match ($comp->salary_basis) {
                            'daily' => round($rate*$payableDays,2),
                            'hourly' => round($rate*$workedHours,2),
                            default => $rate,
                        };
                        $allowances = 0.0; $deductions = 0.0; $lines = [];
                        $lines[]=['code'=>'BASE','label'=>'الراتب الأساسي','kind'=>'base','amount'=>$base];
                        foreach ($employee->payComponents as $component) {
                            if ($component->effective_from > $end->toDateString() || ($component->effective_to && $component->effective_to < $start->toDateString())) continue;
                            $amount = (float)$component->amount;
                            if ($component->kind === 'allowance') $allowances += $amount;
                            else $deductions += $amount;
                            $lines[]=['code'=>$component->code,'label'=>$component->label,'kind'=>$component->kind,'amount'=>$amount,'source_type'=>'recurring','source_id'=>$component->id];
                        }
                        if ($comp->salary_basis === 'monthly' && $unpaidDays && $workDays) {
                            $absence = round($rate*$unpaidDays/$workDays,2);
                            $deductions += $absence;
                            $lines[]=['code'=>'UNPAID','label'=>'خصم الغياب والإجازة غير المدفوعة','kind'=>'deduction','amount'=>$absence,'source_type'=>'attendance'];
                        }
                        $entry = $run->entries()->create([
                            'employee_id'=>$employee->id,'employee_compensation_id'=>$comp->id,'currency'=>$comp->currency,
                            'base_amount'=>$base,'allowance_total'=>round($allowances,2),'deduction_total'=>round($deductions,2),
                            'gross_amount'=>round($base+$allowances,2),'net_amount'=>round($base+$allowances-$deductions,2),
                            'payable_days'=>$payableDays,'unpaid_days'=>$unpaidDays,'worked_hours'=>$workedHours,
                        ]);
                        $entry->lines()->createMany($lines);
                    }
                });
            return $run;
        });
    }

    public function approve(User $actor, PayrollRun $run): void
    {
        DB::transaction(function () use ($actor,$run) {
            $run = PayrollRun::whereKey($run->id)->lockForUpdate()->firstOrFail();
            abort_unless($run->status === 'calculated', 409);
            $period = PayrollPeriod::whereKey($run->payroll_period_id)->lockForUpdate()->first();
            abort_unless($period->status === 'open', 409);
            $run->update(['status'=>'approved','approved_by_user_id'=>$actor->id,'approved_at'=>now()]);
            $run->entries()->update(['payslip_published_at'=>now()]);
            $period->update(['status'=>'locked']);
        });
    }
}
