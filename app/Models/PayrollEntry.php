<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PayrollEntry extends Model
{
    protected $guarded = [];
    public function run(): BelongsTo { return $this->belongsTo(PayrollRun::class, 'payroll_run_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function compensation(): BelongsTo { return $this->belongsTo(EmployeeCompensation::class, 'employee_compensation_id'); }
    public function lines(): HasMany { return $this->hasMany(PayrollEntryLine::class); }
}
