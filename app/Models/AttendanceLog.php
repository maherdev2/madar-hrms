<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AttendanceLog extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['work_date'=>'date','clock_in_at'=>'datetime','clock_out_at'=>'datetime']; }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function leaveRequest(): BelongsTo { return $this->belongsTo(LeaveRequest::class); }
}
