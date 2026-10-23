<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $guarded = [];
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function manager(): BelongsTo { return $this->belongsTo(Employee::class, 'manager_employee_id'); }
    public function employees(): HasMany { return $this->hasMany(Employee::class); }
}
