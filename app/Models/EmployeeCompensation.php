<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmployeeCompensation extends Model
{
    protected $table = 'employee_compensations';
    protected $guarded = [];
    protected function casts(): array { return ['effective_from'=>'date','effective_to'=>'date','base_rate'=>'decimal:2']; }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
