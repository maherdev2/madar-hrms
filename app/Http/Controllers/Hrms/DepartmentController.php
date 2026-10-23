<?php
namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Services\HrmsAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index(Request $request, HrmsAccess $access)
    {
        $access->require($request->user(),'department.manage');
        return Inertia::render('Hrms/Departments', [
            'departments'=>Department::with('manager')->withCount('employees')->orderBy('name')->get(),
            'employees'=>Employee::orderBy('first_name')->get(['id','first_name','last_name','department_id']),
        ]);
    }
    public function store(Request $request, HrmsAccess $access)
    {
        $access->require($request->user(),'department.manage');
        Department::create($request->validate(['code'=>['required','string','max:30','unique:departments,code'],'name'=>['required','string','max:255'],'manager_employee_id'=>['nullable','exists:employees,id']]));
        return back()->with('success','تمت إضافة القسم.');
    }
    public function update(Request $request, Department $department, HrmsAccess $access)
    {
        $access->require($request->user(),'department.manage');
        $department->update($request->validate(['code'=>['required','string','max:30',Rule::unique('departments')->ignore($department->id)],'name'=>['required','string','max:255'],'manager_employee_id'=>['nullable','exists:employees,id']]));
        return back()->with('success','تم تحديث القسم.');
    }
}
