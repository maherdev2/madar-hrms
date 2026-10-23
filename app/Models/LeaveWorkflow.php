<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class LeaveWorkflow extends Model
{
    protected $guarded = [];
    public function steps(): HasMany { return $this->hasMany(LeaveWorkflowStep::class)->orderBy('step_order'); }
    public function requests(): HasMany { return $this->hasMany(LeaveRequest::class); }
}
