<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PerformanceReviewScore extends Model
{
    protected $guarded = [];
    public function review(): BelongsTo { return $this->belongsTo(PerformanceReview::class, 'performance_review_id'); }
    public function criterion(): BelongsTo { return $this->belongsTo(ReviewCriterion::class, 'review_criterion_id'); }
}
