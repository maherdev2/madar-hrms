<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PayrollRun extends Model
{
    protected $guarded = [];
    public function period(): BelongsTo { return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id'); }
    public function entries(): HasMany { return $this->hasMany(PayrollEntry::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by_user_id'); }
}
