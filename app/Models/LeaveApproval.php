<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LeaveApproval extends Model
{
    protected $guarded = [];
    public function request(): BelongsTo { return $this->belongsTo(LeaveRequest::class, 'leave_request_id'); }
    public function step(): BelongsTo { return $this->belongsTo(LeaveWorkflowStep::class, 'leave_workflow_step_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_user_id'); }
}
