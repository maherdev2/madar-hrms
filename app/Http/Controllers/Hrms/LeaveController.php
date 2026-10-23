<?php
namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\LeaveEntitlement;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\HrmsAccess;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LeaveController extends Controller
{
    public function index(Request $request, HrmsAccess $access)
    {
        $user = $request->user();
        $visible = $access->visibleEmployees($user)->pluck('id');
        $requests = LeaveRequest::with(['employee.department','department','type','currentStep.approverRole','approvals.step.approverRole','events'])
            ->whereIn('employee_id',$visible)->latest()->get();
        $balance = $user->employee ? LeaveEntitlement::with('leaveType')->where('employee_id',$user->employee->id)->where('year',today()->year)->get()->map(function($e) use ($user) {
            $used = LeaveRequest::where('employee_id',$user->employee->id)->where('leave_type_id',$e->leave_type_id)->where('status','approved')->whereYear('starts_on',$e->year)->sum('requested_days');
            $e->available_days = round((float)$e->allocated_days+(float)$e->carried_days-(float)$used,2);
            return $e;
        }) : [];
        return Inertia::render('Hrms/Leaves', [
            'requests'=>$requests, 'types'=>LeaveType::where('is_active',true)->get(), 'balances'=>$balance,
            'canRequest'=>(bool)$user->employee, 'canApprove'=>$access->manager($user),
        ]);
    }

    public function store(Request $request, LeaveService $service)
    {
        $data = $request->validate([
            'leave_type_id'=>['required','exists:leave_types,id'], 'starts_on'=>['required','date'],
            'ends_on'=>['required','date','after_or_equal:starts_on'], 'reason'=>['nullable','string','max:2000'],
        ]);
        $service->submit($request->user(),$data);
        return back()->with('success','تم إرسال طلب الإجازة.');
    }

    public function decide(Request $request, LeaveRequest $leave, LeaveService $service, HrmsAccess $access)
    {
        $data = $request->validate(['decision'=>['required',Rule::in(['approved','rejected'])],'comment'=>['nullable','string','max:2000']]);
        $service->decide($request->user(),$leave,$data['decision'],$data['comment'] ?? null,$access);
        return back()->with('success',$data['decision']==='approved' ? 'تمت الموافقة على الخطوة.' : 'تم رفض الطلب.');
    }

    public function cancel(Request $request, LeaveRequest $leave)
    {
        abort_unless($leave->employee_id === $request->user()->employee?->id,403);
        DB::transaction(function () use ($request,$leave) {
            $leave = LeaveRequest::whereKey($leave->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($leave->status,['draft','pending']),409);
            $from = $leave->status;
            $leave->update(['status'=>'cancelled','current_step_id'=>null,'decided_at'=>now()]);
            $leave->events()->create(['actor_user_id'=>$request->user()->id,'from_status'=>$from,'to_status'=>'cancelled','created_at'=>now()]);
        });
        return back()->with('success','تم إلغاء الطلب.');
    }
}
