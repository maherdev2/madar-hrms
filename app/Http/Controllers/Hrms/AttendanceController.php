<?php
namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Services\HrmsAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request, HrmsAccess $access)
    {
        $ids = $access->visibleEmployees($request->user())->pluck('id');
        return Inertia::render('Hrms/Attendance', [
            'logs'=>AttendanceLog::with('employee')->whereIn('employee_id',$ids)->latest('work_date')->take(150)->get(),
            'employees'=>$access->admin($request->user()) ? Employee::orderBy('first_name')->get(['id','first_name','last_name']) : [],
            'canManage'=>$access->admin($request->user()),
        ]);
    }
    public function store(Request $request, HrmsAccess $access)
    {
        $access->require($request->user(),'attendance.manage');
        $data = $request->validate([
            'employee_id'=>['required','exists:employees,id'],'work_date'=>['required','date'],
            'status'=>['required',Rule::in(['present','absent','leave','holiday'])],
            'clock_in_at'=>['nullable','date'],'clock_out_at'=>['nullable','date','after_or_equal:clock_in_at'],
            'worked_hours'=>['nullable','numeric','min:0','max:24'],'note'=>['nullable','string','max:1000'],
        ]);
        $log = AttendanceLog::where('employee_id',$data['employee_id'])->whereDate('work_date',$data['work_date'])->first();
        $log ? $log->update($data) : AttendanceLog::create($data);
        return back()->with('success','تم حفظ سجل الحضور.');
    }
}
