<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PerformanceReview extends Model
{
    protected $guarded = [];
    public function period(): BelongsTo { return $this->belongsTo(ReviewPeriod::class, 'review_period_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(Employee::class, 'reviewer_employee_id'); }
    public function scores(): HasMany { return $this->hasMany(PerformanceReviewScore::class); }
}
