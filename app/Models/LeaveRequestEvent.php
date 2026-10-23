<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LeaveRequestEvent extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    public function request(): BelongsTo { return $this->belongsTo(LeaveRequest::class, 'leave_request_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_user_id'); }
}
