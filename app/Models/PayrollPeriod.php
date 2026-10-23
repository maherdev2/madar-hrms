<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PayrollPeriod extends Model
{
    protected $guarded = [];
    public function runs(): HasMany { return $this->hasMany(PayrollRun::class); }
}
