<?php
namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\HrmsAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index(Request $request, HrmsAccess $access)
    {
        $access->require($request->user(), $access->admin($request->user()) ? 'employee.manage' : ($access->manager($request->user()) ? 'employee.view_team' : 'profile.view_self'));
        $employees = $access->visibleEmployees($request->user())->with(['department','user','currentCompensation'])->orderBy('first_name')->get();
        if (!$access->admin($request->user())) $employees->each(fn ($e) => $e->unsetRelation('currentCompensation'));
        return Inertia::render('Hrms/Employees', [
            'employees'=>$employees, 'departments'=>Department::orderBy('name')->get(['id','name']),
            'canManage'=>$access->admin($request->user()),
        ]);
    }

    public function store(Request $request, HrmsAccess $access)
    {
        $access->require($request->user(),'employee.manage');
        $data = $this->validated($request);
        DB::transaction(function () use ($request,$data) {
            $userId = null;
            if (!empty($data['account_email'])) {
                $user = User::create(['name'=>$data['first_name'].' '.$data['last_name'], 'email'=>$data['account_email'], 'password'=>$data['account_password'], 'is_active'=>true]);
                $user->roles()->attach(DB::table('roles')->where('code','employee')->value('id'));
                $userId = $user->id;
            }
            $employee = Employee::create([...collect($data)->except(['account_email','account_password','salary_basis','base_rate','currency','effective_from'])->all(), 'user_id'=>$userId]);
            if (isset($data['base_rate'])) $employee->compensations()->create([
                'salary_basis'=>$data['salary_basis'],'base_rate'=>$data['base_rate'],'currency'=>$data['currency'],
                'effective_from'=>$data['effective_from'] ?? $data['hire_date'],'created_by_user_id'=>$request->user()->id,
            ]);
        });
        return back()->with('success','تمت إضافة الموظف.');
    }

    public function update(Request $request, Employee $employee, HrmsAccess $access)
    {
        $access->require($request->user(),'employee.manage');
        $data = $this->validated($request, $employee);
        DB::transaction(function () use ($request,$employee,$data) {
            $employee->update(collect($data)->except(['account_email','account_password','salary_basis','base_rate','currency','effective_from'])->all());
            if (isset($data['base_rate'])) {
                $effectiveFrom = $data['effective_from'] ?? today()->toDateString();
                $values = ['salary_basis'=>$data['salary_basis'],'base_rate'=>$data['base_rate'],
                    'currency'=>$data['currency'],'created_by_user_id'=>$request->user()->id];
                $compensation = $employee->compensations()->whereDate('effective_from',$effectiveFrom)->first();
                $compensation
                    ? $compensation->update($values)
                    : $employee->compensations()->create([...$values,'effective_from'=>$effectiveFrom]);
            }
        });
        return back()->with('success','تم تحديث بيانات الموظف.');
    }

    public function destroy(Request $request, Employee $employee, HrmsAccess $access)
    {
        $access->require($request->user(),'employee.manage');
        abort_if($employee->id === $request->user()->employee?->id, 422);
        $employee->update(['employment_status'=>'terminated','termination_date'=>today()]);
        $employee->user?->update(['is_active'=>false]);
        $employee->delete();
        return back()->with('success','تم أرشفة الموظف.');
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $required = $employee ? 'sometimes' : 'required';
        return $request->validate([
            'employee_number'=>[$required,'string','max:30',Rule::unique('employees')->ignore($employee?->id)],
            'first_name'=>[$required,'string','max:255'], 'last_name'=>[$required,'string','max:255'],
            'department_id'=>[$required,'exists:departments,id'], 'job_title'=>[$required,'string','max:255'],
            'hire_date'=>[$required,'date'], 'contract_type'=>[$required,Rule::in(['full_time','part_time','fixed_term','contractor'])],
            'employment_status'=>['sometimes',Rule::in(['active','on_leave','terminated'])],
            'phone'=>['nullable','string','max:40'], 'personal_email'=>['nullable','email','max:255'],
            'date_of_birth'=>['nullable','date'], 'address'=>['nullable','string'],
            'reports_to_id'=>['nullable','exists:employees,id'],
            'account_email'=>['nullable','email',Rule::unique('users','email')],
            'account_password'=>['nullable','required_with:account_email','string','min:8'],
            'salary_basis'=>['required_with:base_rate',Rule::in(['monthly','daily','hourly'])],
            'base_rate'=>['nullable','numeric','min:0'], 'currency'=>['required_with:base_rate','size:3'],
            'effective_from'=>['nullable','date'],
        ]);
    }
}
