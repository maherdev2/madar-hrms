<?php

use App\Http\Controllers\Hrms\AttendanceController;
use App\Http\Controllers\Hrms\DashboardController;
use App\Http\Controllers\Hrms\DepartmentController;
use App\Http\Controllers\Hrms\EmployeeController;
use App\Http\Controllers\Hrms\LeaveController;
use App\Http\Controllers\Hrms\PayrollController;
use App\Http\Controllers\Hrms\PerformanceController;
use App\Http\Controllers\Hrms\SettingsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/employees', [EmployeeController::class,'index'])->name('employees.index');
    Route::post('/employees', [EmployeeController::class,'store'])->name('employees.store');
    Route::put('/employees/{employee}', [EmployeeController::class,'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class,'destroy'])->name('employees.destroy');
    Route::get('/departments', [DepartmentController::class,'index'])->name('departments.index');
    Route::post('/departments', [DepartmentController::class,'store'])->name('departments.store');
    Route::put('/departments/{department}', [DepartmentController::class,'update'])->name('departments.update');
    Route::get('/leaves', [LeaveController::class,'index'])->name('leaves.index');
    Route::post('/leaves', [LeaveController::class,'store'])->name('leaves.store');
    Route::post('/leaves/{leave}/decision', [LeaveController::class,'decide'])->name('leaves.decide');
    Route::post('/leaves/{leave}/cancel', [LeaveController::class,'cancel'])->name('leaves.cancel');
    Route::get('/attendance', [AttendanceController::class,'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class,'store'])->name('attendance.store');
    Route::get('/payroll', [PayrollController::class,'index'])->name('payroll.index');
    Route::post('/payroll/calculate', [PayrollController::class,'calculate'])->name('payroll.calculate');
    Route::post('/payroll/runs/{run}/approve', [PayrollController::class,'approve'])->name('payroll.approve');
    Route::get('/payroll/slips/{entry}', [PayrollController::class,'slip'])->name('payroll.slip');
    Route::get('/payroll/slips/{entry}/download', [PayrollController::class,'download'])->name('payroll.download');
    Route::get('/performance', [PerformanceController::class,'index'])->name('performance.index');
    Route::post('/performance', [PerformanceController::class,'store'])->name('performance.store');
    Route::post('/performance/periods', [PerformanceController::class,'storePeriod'])->name('performance.periods.store');
    Route::get('/settings', [SettingsController::class,'index'])->name('settings.index');
    Route::post('/settings/users/{user}/role', [SettingsController::class,'assignRole'])->name('settings.role');
    Route::post('/settings/entitlements', [SettingsController::class,'entitlement'])->name('settings.entitlement');
    Route::post('/settings/workflows', [SettingsController::class,'workflow'])->name('settings.workflow');
    Route::post('/settings/employees/{employee}/components', [SettingsController::class,'component'])->name('settings.component');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
