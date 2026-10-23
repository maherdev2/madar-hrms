<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class LeaveRequest extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['starts_on'=>'date','ends_on'=>'date','submitted_at'=>'datetime','decided_at'=>'datetime']; }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function type(): BelongsTo { return $this->belongsTo(LeaveType::class, 'leave_type_id'); }
    public function workflow(): BelongsTo { return $this->belongsTo(LeaveWorkflow::class, 'leave_workflow_id'); }
    public function currentStep(): BelongsTo { return $this->belongsTo(LeaveWorkflowStep::class, 'current_step_id'); }
    public function approvals(): HasMany { return $this->hasMany(LeaveApproval::class); }
    public function events(): HasMany { return $this->hasMany(LeaveRequestEvent::class); }
}
