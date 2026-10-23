<?php
namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class HrmsAccess
{
    public function admin(User $user): bool { return $user->hasRole('hr_admin') || $user->hasRole('super_admin'); }
    public function super(User $user): bool { return $user->hasRole('super_admin'); }
    public function manager(User $user): bool { return $user->hasRole('department_manager') || $this->admin($user); }
    public function require(User $user, string $permission): void { abort_unless($user->hasPermission($permission), 403); }

    public function visibleEmployees(User $user): Builder
    {
        $query = Employee::query();
        if ($this->admin($user)) return $query;
        $employee = $user->employee;
        if (!$employee) return $query->whereRaw('1 = 0');
        if ($user->hasRole('department_manager')) {
            return $query->where(function (Builder $q) use ($employee) {
                $q->where('id', $employee->id)
                  ->orWhereHas('department', fn (Builder $d) => $d->where('manager_employee_id', $employee->id));
            });
        }
        return $query->whereKey($employee->id);
    }

    public function canSeeEmployee(User $user, Employee $employee): bool
    {
        return $this->visibleEmployees($user)->whereKey($employee->id)->exists();
    }
}
