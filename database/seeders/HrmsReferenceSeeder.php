<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HrmsReferenceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $roles = [
                'employee' => ['name' => 'موظف', 'parent' => null],
                'department_manager' => ['name' => 'مدير قسم', 'parent' => 'employee'],
                'hr_admin' => ['name' => 'مسؤول الموارد البشرية', 'parent' => 'department_manager'],
                'super_admin' => ['name' => 'مدير النظام', 'parent' => 'hr_admin'],
            ];

            $roleIds = [];
            foreach ($roles as $code => $definition) {
                DB::table('roles')->insertOrIgnore([
                    'code' => $code,
                    'name' => $definition['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('roles')->where('code', $code)->update([
                    'name' => $definition['name'],
                    'parent_id' => $definition['parent'] ? $roleIds[$definition['parent']] : null,
                    'updated_at' => now(),
                ]);
                $roleIds[$code] = DB::table('roles')->where('code', $code)->value('id');
            }

            $permissions = [
                'profile.view_self' => ['View own employee profile', 'employee'],
                'leave.request_self' => ['Submit own leave requests', 'employee'],
                'leave.view_self' => ['View own leave requests', 'employee'],
                'attendance.view_self' => ['View own attendance', 'employee'],
                'payslip.view_self' => ['View own published payslips', 'employee'],
                'employee.view_team' => ['View employees in assigned department', 'department_manager'],
                'leave.approve_department' => ['Decide department leave approval steps', 'department_manager'],
                'performance.evaluate_team' => ['Evaluate employees in assigned department', 'department_manager'],
                'employee.manage' => ['Manage all employee records', 'hr_admin'],
                'department.manage' => ['Manage departments', 'hr_admin'],
                'leave.manage_workflows' => ['Manage leave workflows and entitlements', 'hr_admin'],
                'leave.approve_final' => ['Decide final HR leave approval steps', 'hr_admin'],
                'attendance.manage' => ['Manage attendance records', 'hr_admin'],
                'payroll.manage' => ['Calculate payroll', 'hr_admin'],
                'payroll.approve' => ['Approve and publish payroll', 'hr_admin'],
                'performance.manage' => ['Manage review periods and criteria', 'hr_admin'],
                'settings.manage' => ['Manage system settings', 'hr_admin'],
                'rbac.manage' => ['Manage users, roles and permissions', 'super_admin'],
            ];

            foreach ($permissions as $code => [$description, $roleCode]) {
                DB::table('permissions')->insertOrIgnore([
                    'code' => $code,
                    'description' => $description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('permissions')->where('code', $code)->update([
                    'description' => $description,
                    'updated_at' => now(),
                ]);
                $permissionId = DB::table('permissions')->where('code', $code)->value('id');
                DB::table('permission_role')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleIds[$roleCode],
                ]);
            }

            foreach ([
                ['code' => 'annual', 'name' => 'إجازة سنوية', 'is_paid' => true, 'requires_document' => false],
                ['code' => 'sick', 'name' => 'إجازة مرضية', 'is_paid' => true, 'requires_document' => true],
                ['code' => 'unpaid', 'name' => 'إجازة غير مدفوعة', 'is_paid' => false, 'requires_document' => false],
            ] as $type) {
                DB::table('leave_types')->insertOrIgnore([
                    'code' => $type['code'],
                    'name' => $type['name'],
                    'is_paid' => $type['is_paid'],
                    'requires_document' => $type['requires_document'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('leave_types')->where('code', $type['code'])->update([
                    'name' => $type['name'],
                    'is_paid' => $type['is_paid'],
                    'requires_document' => $type['requires_document'],
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
