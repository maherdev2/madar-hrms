<?php
namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\LeaveWorkflow;
use App\Models\Role;
use App\Models\User;
use App\Services\HrmsAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request, HrmsAccess $access)
    {
        $access->require($request->user(),'settings.manage');
        return Inertia::render('Hrms/Settings', [
            'users'=>User::with('roles','employee')->orderBy('name')->get(['id','name','email','is_active']),
            'roles'=>Role::orderBy('id')->get(['id','code','name']),
            'workflows'=>LeaveWorkflow::with('steps.approverRole')->orderBy('priority')->get(),
            'leaveTypes'=>LeaveType::orderBy('id')->get(),
            'employees'=>Employee::orderBy('first_name')->get(['id','first_name','last_name']),
            'entitlements'=>LeaveEntitlement::with(['employee','leaveType'])->where('year',today()->year)->get(),
        ]);
    }

    public function assignRole(Request $request, User $user, HrmsAccess $access)
    {
        $access->require($request->user(),'rbac.manage');
        $data = $request->validate(['role_id'=>['required','exists:roles,id']]);
        abort_if($user->id === $request->user()->id && Role::find($data['role_id'])->code !== 'super_admin',422);
        $user->roles()->sync([$data['role_id']]);
        return back()->with('success','تم تحديث دور المستخدم.');
    }

    public function entitlement(Request $request, HrmsAccess $access)
    {
        $access->require($request->user(),'leave.manage_workflows');
        $data = $request->validate([
            'employee_id'=>['required','exists:employees,id'],'leave_type_id'=>['required','exists:leave_types,id'],
            'year'=>['required','integer','between:2020,2100'],
            'allocated_days'=>['required','numeric','min:0','max:365'], 'carried_days'=>['required','numeric','min:0','max:365'],
        ]);
        LeaveEntitlement::updateOrCreate(
            collect($data)->only(['employee_id','leave_type_id','year'])->all(),
            collect($data)->only(['allocated_days','carried_days'])->all()
        );
        return back()->with('success','تم تحديث رصيد الإجازة.');
    }

    public function workflow(Request $request, HrmsAccess $access)
    {
        $access->require($request->user(),'leave.manage_workflows');
        $data = $request->validate([
            'name'=>['required','string','max:255'], 'department_id'=>['nullable','exists:departments,id'],
            'leave_type_id'=>['nullable','exists:leave_types,id'], 'requester_role_id'=>['nullable','exists:roles,id'],
            'priority'=>['required','integer','min:0','max:9999'], 'effective_from'=>['required','date'],
            'steps'=>['required','array','min:1'], 'steps.*.approver_role_id'=>['required','exists:roles,id'],
            'steps.*.approver_scope'=>['required',Rule::in(['global','department_manager'])],
        ]);
        DB::transaction(function () use ($data) {
            $workflow = LeaveWorkflow::create([...collect($data)->except('steps')->all(),'is_active'=>true]);
            foreach ($data['steps'] as $index=>$step) $workflow->steps()->create([...$step,'step_order'=>$index+1]);
        });
        return back()->with('success','تم إنشاء مسار الموافقة.');
    }

    public function component(Request $request, Employee $employee, HrmsAccess $access)
    {
        $access->require($request->user(),'payroll.manage');
        $data = $request->validate([
            'code'=>['required','string','max:50'],'label'=>['required','string','max:255'],
            'kind'=>['required',Rule::in(['allowance','deduction'])], 'amount'=>['required','numeric','min:0'],
            'effective_from'=>['required','date'], 'effective_to'=>['nullable','date','after_or_equal:effective_from'],
        ]);
        $employee->payComponents()->create($data);
        return back()->with('success','تمت إضافة بند الراتب.');
    }
}
