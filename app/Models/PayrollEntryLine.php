<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PayrollEntryLine extends Model
{
    protected $guarded = [];
    public function entry(): BelongsTo { return $this->belongsTo(PayrollEntry::class, 'payroll_entry_id'); }
}
