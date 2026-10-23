<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LeaveWorkflowStep extends Model
{
    protected $guarded = [];
    public function workflow(): BelongsTo { return $this->belongsTo(LeaveWorkflow::class, 'leave_workflow_id'); }
    public function approverRole(): BelongsTo { return $this->belongsTo(Role::class, 'approver_role_id'); }
}
