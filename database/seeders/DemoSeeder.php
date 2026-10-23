<?php
namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveWorkflow;
use App\Models\PerformanceReview;
use App\Models\ReviewCriterion;
use App\Models\ReviewPeriod;
use App\Models\Role;
use App\Models\User;
use App\Services\HrmsAccess;
use App\Services\LeaveService;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) return;
        $roles = Role::pluck('id','code');
        $admin = $this->account('admin@madar.test','سارة العتيبي','super_admin',$roles);
        $hr = $this->account('hr@madar.test','ليان الحربي','hr_admin',$roles);
        $manager = $this->account('manager@madar.test','خالد القحطاني','department_manager',$roles);
        $employeeUser = $this->account('employee@madar.test','ريم السالم','employee',$roles);

        $engineering = Department::firstOrCreate(['code'=>'ENG'],['name'=>'التقنية','is_active'=>true]);
        $operations = Department::firstOrCreate(['code'=>'OPS'],['name'=>'العمليات','is_active'=>true]);
        $people = Department::firstOrCreate(['code'=>'HR'],['name'=>'الموارد البشرية','is_active'=>true]);
        $records = [
            ['E001','سارة','العتيبي','مدير النظام',$people,$admin,25000],
            ['E002','ليان','الحربي','مدير الموارد البشرية',$people,$hr,18000],
            ['E003','خالد','القحطاني','مدير التقنية',$engineering,$manager,22000],
            ['E004','ريم','السالم','مهندسة برمجيات',$engineering,$employeeUser,14000],
            ['E005','عبدالله','الغامدي','مهندس نظم',$engineering,null,13500],
            ['E006','نورة','الدوسري','محللة بيانات',$engineering,null,12500],
            ['E007','محمد','الشهري','منسق عمليات',$operations,null,10500],
            ['E008','هدى','المالكي','أخصائية توظيف',$people,null,11500],
            ['E009','ياسر','الزهراني','مصمم تجربة',$engineering,null,12000],
            ['E010','أمل','العمري','مشرفة عمليات',$operations,null,16000],
        ];
        $employees = [];
        foreach ($records as [$number,$first,$last,$title,$department,$user,$salary]) {
            $e = Employee::firstOrCreate(['employee_number'=>$number],[
                'first_name'=>$first,'last_name'=>$last,'job_title'=>$title,'department_id'=>$department->id,
                'user_id'=>$user?->id,'hire_date'=>now()->subYears(2)->toDateString(),
                'contract_type'=>'full_time','employment_status'=>'active',
            ]);
            $employees[$number]=$e;
            $effectiveFrom = now()->startOfYear()->toDateString();
            if (!$e->compensations()->whereDate('effective_from',$effectiveFrom)->exists()) {
                $e->compensations()->create([
                    'effective_from'=>$effectiveFrom,'salary_basis'=>'monthly','base_rate'=>$salary,
                    'currency'=>'SAR','created_by_user_id'=>$admin->id,
                ]);
            }
            $e->payComponents()->firstOrCreate(['code'=>'HOUSING'],[
                'label'=>'بدل سكن','kind'=>'allowance','amount'=>round($salary*.15,2),
                'effective_from'=>now()->startOfYear()->toDateString(),
            ]);
            foreach (['annual'=>24,'sick'=>14] as $code=>$days) {
                $type = LeaveType::where('code',$code)->first();
                \App\Models\LeaveEntitlement::firstOrCreate(
                    ['employee_id'=>$e->id,'leave_type_id'=>$type->id,'year'=>today()->year],
                    ['allocated_days'=>$days,'carried_days'=>0]
                );
            }
        }
        $engineering->update(['manager_employee_id'=>$employees['E003']->id]);
        $people->update(['manager_employee_id'=>$employees['E002']->id]);
        $operations->update(['manager_employee_id'=>$employees['E010']->id]);
        foreach ($employees as $number=>$e) if (!in_array($number,['E001','E002','E003','E010'])) {
            $e->update(['reports_to_id'=>$e->department_id === $engineering->id ? $employees['E003']->id : ($e->department_id === $operations->id ? $employees['E010']->id : $employees['E002']->id)]);
        }

        $this->workflow('طلب موظف', $roles['employee'], [
            [$roles['department_manager'],'department_manager'],[$roles['hr_admin'],'global'],
        ]);
        $this->workflow('طلب مدير قسم', $roles['department_manager'], [[$roles['hr_admin'],'global']]);
        $this->workflow('طلب مسؤول موارد بشرية', $roles['hr_admin'], [[$roles['super_admin'],'global']]);
        $this->workflow('طلب مدير النظام', $roles['super_admin'], [[$roles['super_admin'],'global']]);

        if (LeaveRequest::count() === 0) {
            $leave = app(LeaveService::class);
            $nextMonday = today()->next(Carbon::MONDAY);
            $leave->submit($employeeUser,['leave_type_id'=>LeaveType::where('code','annual')->value('id'),
                'starts_on'=>$nextMonday->toDateString(),'ends_on'=>$nextMonday->copy()->addDays(2)->toDateString(),
                'reason'=>'إجازة عائلية مخطط لها.']);
            $past = today()->previous(Carbon::MONDAY);
            $approved = $leave->submit($employeeUser,['leave_type_id'=>LeaveType::where('code','sick')->value('id'),
                'starts_on'=>$past->toDateString(),'ends_on'=>$past->copy()->addDay()->toDateString(),
                'reason'=>'راحة طبية.']);
            $leave->decide($manager,$approved,'approved','موافق عليه.',app(HrmsAccess::class));
            $leave->decide($hr,$approved,'approved','تم الاعتماد النهائي.',app(HrmsAccess::class));
        }

        for ($day=today()->startOfMonth(); $day->lte(today()); $day->addDay()) {
            if ($day->isWeekend()) continue;
            foreach ($employees as $number=>$e) {
                $absent = $number === 'E005' && $day->day === 7;
                if (!AttendanceLog::where('employee_id',$e->id)->whereDate('work_date',$day->toDateString())->exists()) {
                    AttendanceLog::create([
                        'employee_id'=>$e->id,'work_date'=>$day->toDateString(),
                        'status'=>$absent ? 'absent' : 'present','worked_hours'=>$absent ? 0 : 8,
                        'note'=>$absent ? 'غياب غير مدفوع' : null,
                    ]);
                }
            }
        }

        $period = ReviewPeriod::firstOrCreate(['frequency'=>'quarterly','starts_on'=>today()->startOfQuarter()->toDateString(),'ends_on'=>today()->endOfQuarter()->toDateString()],['name'=>'مراجعة الربع الحالي']);
        ReviewPeriod::firstOrCreate(['frequency'=>'annual','starts_on'=>today()->startOfYear()->toDateString(),'ends_on'=>today()->endOfYear()->toDateString()],['name'=>'المراجعة السنوية']);
        $criteria = [];
        foreach ([['quality','جودة العمل'],['collaboration','التعاون'],['ownership','تحمل المسؤولية']] as [$code,$label]) {
            $criteria[] = ReviewCriterion::firstOrCreate(['code'=>$code],['label'=>$label,'max_score'=>5,'is_active'=>true]);
        }
        $review = PerformanceReview::firstOrCreate(['review_period_id'=>$period->id,'employee_id'=>$employees['E004']->id],[
            'reviewer_employee_id'=>$employees['E003']->id,'status'=>'submitted','overall_score'=>4.33,
            'summary'=>'أداء قوي ومساهمة واضحة في تسليم المشاريع.','submitted_at'=>now(),
        ]);
        foreach ($criteria as $i=>$criterion) $review->scores()->firstOrCreate(['review_criterion_id'=>$criterion->id],['score'=>[5,4,4][$i]]);

        if (!\App\Models\PayrollRun::whereHas('period',fn($q)=>$q->where('year',today()->year)->where('month',today()->month))->exists()) {
            $run = app(PayrollService::class)->calculate($admin,today()->year,today()->month);
            app(PayrollService::class)->approve($admin,$run);
        }
    }

    private function account(string $email,string $name,string $role,$roles): User
    {
        $user = User::firstOrCreate(['email'=>$email],[
            'name'=>$name,'password'=>'Demo1234!','email_verified_at'=>now(),'is_active'=>true,
        ]);
        $user->roles()->sync([$roles[$role]]);
        return $user;
    }

    private function workflow(string $name,int $requesterRoleId,array $steps): void
    {
        $workflow = LeaveWorkflow::firstOrCreate(['name'=>$name],[
            'requester_role_id'=>$requesterRoleId,'priority'=>10,'is_active'=>true,
            'effective_from'=>today()->startOfYear()->toDateString(),
        ]);
        if ($workflow->steps()->exists()) return;
        foreach ($steps as $index=>[$role,$scope]) $workflow->steps()->create([
            'step_order'=>$index+1,'approver_role_id'=>$role,'approver_scope'=>$scope,
        ]);
    }
}
