<?php
namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PerformanceReview;
use App\Models\ReviewCriterion;
use App\Models\ReviewPeriod;
use App\Services\HrmsAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PerformanceController extends Controller
{
    public function index(Request $request, HrmsAccess $access)
    {
        abort_unless($access->manager($request->user()),403);
        $employees = $access->visibleEmployees($request->user())->where('id','!=',$request->user()->employee?->id ?? 0)->orderBy('first_name')->get(['id','first_name','last_name','department_id']);
        return Inertia::render('Hrms/Performance', [
            'employees'=>$employees, 'periods'=>ReviewPeriod::latest('starts_on')->get(),
            'criteria'=>ReviewCriterion::where('is_active',true)->get(),
            'reviews'=>PerformanceReview::with(['employee','reviewer','period','scores.criterion'])->whereIn('employee_id',$employees->pluck('id'))->latest()->get(),
            'canManagePeriods'=>$access->admin($request->user()),
        ]);
    }
    public function storePeriod(Request $request, HrmsAccess $access)
    {
        $access->require($request->user(),'performance.manage');
        $data = $request->validate([
            'name'=>['required','string','max:255'],
            'frequency'=>['required',Rule::in(['quarterly','annual'])],
            'starts_on'=>['required','date'],
            'ends_on'=>['required','date','after_or_equal:starts_on'],
        ]);
        ReviewPeriod::firstOrCreate(
            collect($data)->only(['frequency','starts_on','ends_on'])->all(),
            ['name'=>$data['name']]
        );
        return back()->with('success','تم إنشاء فترة التقييم.');
    }
    public function store(Request $request, HrmsAccess $access)
    {
        abort_unless($access->manager($request->user()),403);
        $data = $request->validate([
            'employee_id'=>['required','exists:employees,id'],'review_period_id'=>['required','exists:review_periods,id'],
            'summary'=>['nullable','string','max:4000'],'scores'=>['required','array','min:1'],
            'scores.*.criterion_id'=>['required','exists:review_criteria,id'],
            'scores.*.score'=>['required','integer','between:1,5'],
            'scores.*.comment'=>['nullable','string','max:1000'],
        ]);
        $employee = Employee::findOrFail($data['employee_id']);
        abort_unless($request->user()->employee,422);
        abort_unless($access->canSeeEmployee($request->user(),$employee) && $employee->id !== $request->user()->employee?->id,403);
        DB::transaction(function () use ($request,$data,$employee) {
            $review = PerformanceReview::updateOrCreate(
                ['employee_id'=>$employee->id,'review_period_id'=>$data['review_period_id']],
                ['reviewer_employee_id'=>$request->user()->employee->id,
                 'status'=>'submitted','summary'=>$data['summary'] ?? null,
                 'overall_score'=>round(collect($data['scores'])->avg('score'),2),'submitted_at'=>now()]
            );
            $review->scores()->delete();
            foreach ($data['scores'] as $score) {
                $criterion = ReviewCriterion::findOrFail($score['criterion_id']);
                abort_if($score['score'] > $criterion->max_score,422);
                $review->scores()->create(['review_criterion_id'=>$criterion->id,'score'=>$score['score'],'comment'=>$score['comment'] ?? null]);
            }
        });
        return back()->with('success','تم حفظ التقييم.');
    }
}
