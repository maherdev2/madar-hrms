<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 30)->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('reports_to_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('personal_email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('job_title');
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->string('employment_status', 20)->default('active');
            $table->string('contract_type', 20);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['department_id', 'employment_status']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('manager_employee_id')->nullable()->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_employee_id');
        });
        Schema::dropIfExists('employees');
    }
};
