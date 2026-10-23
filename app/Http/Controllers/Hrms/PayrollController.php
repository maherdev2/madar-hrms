<?php
namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Services\HrmsAccess;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function index(Request $request, HrmsAccess $access)
    {
        $user = $request->user();
        $admin = $access->admin($user);
        $runs = $admin ? PayrollRun::with(['period','entries.employee'])->latest()->take(12)->get() : [];
        $slips = $user->employee ? PayrollEntry::with(['run.period','lines'])->where('employee_id',$user->employee->id)->whereNotNull('payslip_published_at')->latest()->get() : [];
        return Inertia::render('Hrms/Payroll', ['runs'=>$runs,'slips'=>$slips,'canManage'=>$admin]);
    }
    public function calculate(Request $request, HrmsAccess $access, PayrollService $service)
    {
        $access->require($request->user(),'payroll.manage');
        $data = $request->validate(['year'=>['required','integer','between:2020,2100'],'month'=>['required','integer','between:1,12']]);
        $service->calculate($request->user(),$data['year'],$data['month']);
        return back()->with('success','تم احتساب دورة الرواتب.');
    }
    public function approve(Request $request, PayrollRun $run, HrmsAccess $access, PayrollService $service)
    {
        $access->require($request->user(),'payroll.approve');
        $service->approve($request->user(),$run);
        return back()->with('success','تم اعتماد الرواتب ونشر القسائم.');
    }
    public function slip(Request $request, PayrollEntry $entry, HrmsAccess $access)
    {
        abort_unless($access->admin($request->user()) || $entry->employee_id === $request->user()->employee?->id,403);
        abort_unless($entry->payslip_published_at,404);
        $entry->load(['employee.department','run.period','lines']);
        return view('payslip',['entry'=>$entry]);
    }

    public function download(Request $request, PayrollEntry $entry, HrmsAccess $access)
    {
        abort_unless($access->admin($request->user()) || $entry->employee_id === $request->user()->employee?->id,403);
        abort_unless($entry->payslip_published_at,404);
        $entry->load(['employee.department','run.period','lines']);
        return response(view('payslip',['entry'=>$entry])->render(),200,[
            'Content-Type'=>'text/html; charset=UTF-8',
            'Content-Disposition'=>'attachment; filename="payslip-'.$entry->run->period->year.'-'.$entry->run->period->month.'.html"',
        ]);
    }
}
