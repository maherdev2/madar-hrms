<?php
namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveEntitlement;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveWorkflow;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function workingDays(string $start, string $end): int
    {
        $from = CarbonImmutable::parse($start);
        $to = CarbonImmutable::parse($end);
        $count = 0;
        for ($day = $from; $day->lte($to); $day = $day->addDay()) if ($day->isWeekday()) $count++;
        return $count;
    }

    public function submit(User $user, array $data): LeaveRequest
    {
        $employee = $user->employee;
        if (!$employee) throw ValidationException::withMessages(['employee' => 'لا يوجد ملف موظف مرتبط بهذا الحساب.']);
        $type = LeaveType::findOrFail($data['leave_type_id']);
        $days = $this->workingDays($data['starts_on'], $data['ends_on']);
        if ($days < 1) throw ValidationException::withMessages(['starts_on' => 'اختر يوم عمل واحدًا على الأقل.']);

        return DB::transaction(function () use ($user, $employee, $type, $days, $data) {
            Employee::whereKey($employee->id)->lockForUpdate()->first();
            $overlap = LeaveRequest::where('employee_id', $employee->id)
                ->whereIn('status', ['pending','approved'])
                ->whereDate('starts_on', '<=', $data['ends_on'])
                ->whereDate('ends_on', '>=', $data['starts_on'])->exists();
            if ($overlap) throw ValidationException::withMessages(['starts_on' => 'توجد إجازة أخرى تتداخل مع هذه المدة.']);

            if ($type->code === 'annual') {
                $year = CarbonImmutable::parse($data['starts_on'])->year;
                if (CarbonImmutable::parse($data['ends_on'])->year !== $year) {
                    throw ValidationException::withMessages(['ends_on' => 'قسّم الإجازة السنوية بين السنتين إلى طلبين.']);
                }
                $entitlement = LeaveEntitlement::where('employee_id', $employee->id)
                    ->where('leave_type_id', $type->id)->where('year', $year)->lockForUpdate()->first();
                $used = LeaveRequest::where('employee_id', $employee->id)->where('leave_type_id', $type->id)
                    ->where('status', 'approved')->whereYear('starts_on', $year)->sum('requested_days');
                $pending = LeaveRequest::where('employee_id', $employee->id)->where('leave_type_id', $type->id)
                    ->where('status', 'pending')->whereYear('starts_on', $year)->sum('requested_days');
                if (!$entitlement || $days > ($entitlement->allocated_days + $entitlement->carried_days - $used - $pending)) {
                    throw ValidationException::withMessages(['starts_on' => 'الرصيد السنوي المتاح لا يكفي لهذه الإجازة.']);
                }
            }

            $roleIds = $user->roles->pluck('id')->all();
            $workflow = LeaveWorkflow::with('steps')->where('is_active', true)
                ->whereDate('effective_from', '<=', $data['starts_on'])
                ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $data['starts_on']))
                ->where(fn ($q) => $q->whereNull('department_id')->orWhere('department_id', $employee->department_id))
                ->where(fn ($q) => $q->whereNull('leave_type_id')->orWhere('leave_type_id', $type->id))
                ->where(fn ($q) => $q->whereNull('requester_role_id')->orWhereIn('requester_role_id', $roleIds))
                ->get()->sortBy(fn ($w) => [-(int)($w->department_id !== null)-(int)($w->leave_type_id !== null)-(int)($w->requester_role_id !== null), $w->priority, $w->id])->first();
            if (!$workflow || $workflow->steps->isEmpty()) throw ValidationException::withMessages(['leave_type_id' => 'لا يوجد مسار موافقة متاح لهذا الطلب.']);

            $request = LeaveRequest::create([
                'employee_id' => $employee->id, 'department_id' => $employee->department_id,
                'leave_type_id' => $type->id, 'leave_workflow_id' => $workflow->id,
                'current_step_id' => $workflow->steps->first()->id,
                'starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on'],
                'requested_days' => $days, 'reason' => $data['reason'] ?? null,
                'status' => 'pending', 'submitted_at' => now(),
            ]);
            foreach ($workflow->steps as $step) $request->approvals()->create(['leave_workflow_step_id' => $step->id, 'status' => 'pending']);
            $request->events()->create(['actor_user_id' => $user->id, 'from_status' => 'draft', 'to_status' => 'pending', 'created_at' => now()]);
            return $request;
        });
    }

    public function decide(User $user, LeaveRequest $request, string $decision, ?string $comment, HrmsAccess $access): void
    {
        DB::transaction(function () use ($user, $request, $decision, $comment, $access) {
            $request = LeaveRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            abort_unless($request->status === 'pending' && $request->currentStep, 409);
            $step = $request->currentStep;
            $role = $step->approverRole->code;
            $allowed = match ($step->approver_scope) {
                'department_manager' => $access->admin($user) || ($user->hasRole('department_manager') && $user->employee?->id === $request->department->manager_employee_id),
                'global' => $role === 'super_admin' ? $access->super($user) : ($access->admin($user) || $user->hasRole($role)),
                default => false,
            };
            abort_unless($allowed && ($step->allow_self_approval || $user->employee?->id !== $request->employee_id), 403);
            $approval = $request->approvals()->where('leave_workflow_step_id', $step->id)->lockForUpdate()->firstOrFail();
            abort_unless($approval->status === 'pending', 409);
            $approval->update(['actor_user_id'=>$user->id,'status'=>$decision,'comment'=>$comment,'decided_at'=>now()]);
            if ($decision === 'rejected') {
                $request->update(['status'=>'rejected','current_step_id'=>null,'decided_at'=>now()]);
                $request->events()->create(['actor_user_id'=>$user->id,'from_status'=>'pending','to_status'=>'rejected','comment'=>$comment,'created_at'=>now()]);
                return;
            }
            $next = $request->workflow->steps()->where('step_order','>',$step->step_order)->first();
            if ($next) {
                $request->update(['current_step_id'=>$next->id]);
                $request->events()->create(['actor_user_id'=>$user->id,'from_status'=>'pending','to_status'=>'pending','comment'=>'اكتملت خطوة الموافقة.','created_at'=>now()]);
            } else {
                $request->update(['status'=>'approved','current_step_id'=>null,'decided_at'=>now()]);
                $request->events()->create(['actor_user_id'=>$user->id,'from_status'=>'pending','to_status'=>'approved','created_at'=>now()]);
            }
        });
    }
}
