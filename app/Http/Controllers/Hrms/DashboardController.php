<?php
namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\PayrollEntry;
use App\Services\HrmsAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request, HrmsAccess $access)
    {
        $user = $request->user();
        $visible = $access->visibleEmployees($user)->pluck('id');
        $leave = LeaveRequest::with(['employee','type','currentStep'])
            ->whereIn('employee_id', $visible)->latest()->take(6)->get();
        return Inertia::render('Hrms/Dashboard', [
            'stats' => [
                'employees' => $visible->count(),
                'pendingLeaves' => LeaveRequest::whereIn('employee_id', $visible)->where('status','pending')->count(),
                'presentToday' => AttendanceLog::whereIn('employee_id', $visible)->whereDate('work_date', today())->where('status','present')->count(),
                'myPayslips' => $user->employee ? PayrollEntry::where('employee_id',$user->employee->id)->whereNotNull('payslip_published_at')->count() : 0,
            ],
            'recentLeaves' => $leave,
        ]);
    }
}
