<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    protected function casts(): array { return ['hire_date' => 'date', 'termination_date' => 'date', 'date_of_birth' => 'date']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function manager(): BelongsTo { return $this->belongsTo(self::class, 'reports_to_id'); }
    public function reports(): HasMany { return $this->hasMany(self::class, 'reports_to_id'); }
    public function compensations(): HasMany { return $this->hasMany(EmployeeCompensation::class); }
    public function currentCompensation(): HasOne { return $this->hasOne(EmployeeCompensation::class)->latestOfMany('effective_from'); }
    public function payComponents(): HasMany { return $this->hasMany(RecurringPayComponent::class); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
    public function attendanceLogs(): HasMany { return $this->hasMany(AttendanceLog::class); }
    public function payrollEntries(): HasMany { return $this->hasMany(PayrollEntry::class); }
    public function performanceReviews(): HasMany { return $this->hasMany(PerformanceReview::class); }
    public function getFullNameAttribute(): string { return trim($this->first_name.' '.$this->last_name); }
}
