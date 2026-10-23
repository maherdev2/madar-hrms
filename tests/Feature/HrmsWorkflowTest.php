<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveWorkflow;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\HrmsReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HrmsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HrmsReferenceSeeder::class);
    }

    public function test_roles_and_employee_visibility_are_scoped(): void
    {
        $department = Department::create(['code'=>'ENG','name'=>'التقنية','is_active'=>true]);
        $other = Department::create(['code'=>'OPS','name'=>'العمليات','is_active'=>true]);
        $manager = $this->userWithEmployee('department_manager',$department,'M001');
        $member = $this->userWithEmployee('employee',$department,'E001');
        $outsider = $this->userWithEmployee('employee',$other,'E002');
        $department->update(['manager_employee_id'=>$manager->employee->id]);

        $this->assertTrue($manager->hasPermission('profile.view_self'));
        $this->assertTrue($manager->hasPermission('employee.view_team'));
        $this->assertFalse($manager->hasPermission('payroll.manage'));

        $this->actingAs($manager)->get('/employees')->assertOk()->assertInertia(fn (Assert $page) =>
            $page->component('Hrms/Employees')->has('employees',2)
        );
        $this->actingAs($member)->get('/employees')->assertOk()->assertInertia(fn (Assert $page) =>
            $page->component('Hrms/Employees')->has('employees',1)
        );
        $this->actingAs($outsider)->post('/payroll/calculate',['year'=>2026,'month'=>10])->assertForbidden();
        $this->actingAs($member)->get('/performance')->assertForbidden();
    }

    public function test_leave_request_moves_through_manager_and_hr_steps(): void
    {
        $department = Department::create(['code'=>'ENG','name'=>'التقنية','is_active'=>true]);
        $manager = $this->userWithEmployee('department_manager',$department,'M001');
        $employee = $this->userWithEmployee('employee',$department,'E001');
        $hr = $this->userWithEmployee('hr_admin',$department,'H001');
        $department->update(['manager_employee_id'=>$manager->employee->id]);

        $workflow = LeaveWorkflow::create([
            'name'=>'مسار اختبار','requester_role_id'=>Role::where('code','employee')->value('id'),
            'priority'=>1,'is_active'=>true,'effective_from'=>'2026-01-01',
        ]);
        $workflow->steps()->create(['step_order'=>1,'approver_role_id'=>Role::where('code','department_manager')->value('id'),'approver_scope'=>'department_manager']);
        $workflow->steps()->create(['step_order'=>2,'approver_role_id'=>Role::where('code','hr_admin')->value('id'),'approver_scope'=>'global']);

        $this->actingAs($employee)->post('/leaves',[
            'leave_type_id'=>LeaveType::where('code','sick')->value('id'),
            'starts_on'=>'2026-10-19','ends_on'=>'2026-10-20','reason'=>'إجازة تجريبية',
        ])->assertSessionHasNoErrors();

        $leave = LeaveRequest::firstOrFail();
        $this->assertSame('pending',$leave->status);
        $this->assertCount(2,$leave->approvals);
        $this->actingAs($employee)->post("/leaves/{$leave->id}/decision",['decision'=>'approved'])->assertForbidden();
        $this->actingAs($manager)->post("/leaves/{$leave->id}/decision",['decision'=>'approved'])->assertSessionHasNoErrors();
        $this->assertSame('pending',$leave->fresh()->status);
        $this->actingAs($hr)->post("/leaves/{$leave->id}/decision",['decision'=>'approved'])->assertSessionHasNoErrors();
        $this->assertSame('approved',$leave->fresh()->status);
        $this->assertNull($leave->fresh()->current_step_id);
    }

    public function test_hr_admin_can_update_employee_without_changing_account_or_salary(): void
    {
        $department = Department::create(['code'=>'ENG','name'=>'التقنية','is_active'=>true]);
        $admin = $this->userWithEmployee('hr_admin',$department,'H001');
        $employee = $this->userWithEmployee('employee',$department,'E001')->employee;

        $this->actingAs($admin)->put("/employees/{$employee->id}",[
            'employee_number'=>'E001','first_name'=>'اسم معدل','last_name'=>'تجريبي',
            'department_id'=>$department->id,'job_title'=>'مهندس','hire_date'=>'2025-01-01',
            'contract_type'=>'full_time','employment_status'=>'active','phone'=>'',
            'personal_email'=>'','account_email'=>'','account_password'=>'',
            'salary_basis'=>'monthly','base_rate'=>'','currency'=>'SAR','effective_from'=>'',
        ])->assertSessionHasNoErrors();

        $this->assertSame('اسم معدل',$employee->fresh()->first_name);
    }

    public function test_repeated_attendance_and_compensation_edits_update_existing_rows(): void
    {
        $department = Department::create(['code'=>'ENG','name'=>'التقنية','is_active'=>true]);
        $admin = $this->userWithEmployee('hr_admin',$department,'H001');
        $employee = $this->userWithEmployee('employee',$department,'E001')->employee;

        foreach ([8,7] as $hours) {
            $this->actingAs($admin)->post('/attendance',[
                'employee_id'=>$employee->id,'work_date'=>'2026-10-16',
                'status'=>'present','worked_hours'=>$hours,
            ])->assertSessionHasNoErrors();
        }
        $this->assertSame(1,AttendanceLog::where('employee_id',$employee->id)->count());
        $this->assertEquals(7,AttendanceLog::first()->worked_hours);

        foreach ([14000,15000] as $salary) {
            $this->actingAs($admin)->put("/employees/{$employee->id}",[
                'employee_number'=>'E001','first_name'=>'موظف','last_name'=>'تجريبي',
                'department_id'=>$department->id,'job_title'=>'مهندس','hire_date'=>'2025-01-01',
                'contract_type'=>'full_time','salary_basis'=>'monthly','base_rate'=>$salary,
                'currency'=>'SAR','effective_from'=>'2026-10-01',
            ])->assertSessionHasNoErrors();
        }
        $this->assertSame(1,$employee->compensations()->count());
        $this->assertSame('15000.00',$employee->compensations()->first()->base_rate);
    }

    private function userWithEmployee(string $role, Department $department, string $number): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('code',$role)->value('id'));
        Employee::create([
            'employee_number'=>$number,'user_id'=>$user->id,'department_id'=>$department->id,
            'first_name'=>'موظف','last_name'=>$number,'job_title'=>'اختبار',
            'hire_date'=>'2025-01-01','contract_type'=>'full_time','employment_status'=>'active',
        ]);
        return $user->load('employee','roles');
    }
}
