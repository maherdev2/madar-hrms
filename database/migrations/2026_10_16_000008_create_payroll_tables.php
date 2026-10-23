<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('open'); // open, locked
            $table->timestamps();
            $table->unique(['year', 'month']);
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('revision')->default(1);
            $table->string('status', 20)->default('draft'); // draft, calculated, approved, void
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['payroll_period_id', 'revision']);
        });

        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_compensation_id')->constrained()->restrictOnDelete();
            $table->char('currency', 3);
            $table->decimal('base_amount', 14, 2);
            $table->decimal('allowance_total', 14, 2)->default(0);
            $table->decimal('deduction_total', 14, 2)->default(0);
            $table->decimal('gross_amount', 14, 2);
            $table->decimal('net_amount', 14, 2);
            $table->decimal('payable_days', 7, 2)->default(0);
            $table->decimal('unpaid_days', 7, 2)->default(0);
            $table->decimal('worked_hours', 8, 2)->default(0);
            $table->timestamp('payslip_published_at')->nullable();
            $table->timestamps();
            $table->unique(['payroll_run_id', 'employee_id']);
        });

        Schema::create('payroll_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_entry_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('label');
            $table->string('kind', 20); // base, allowance, deduction
            $table->decimal('amount', 14, 2);
            $table->string('source_type', 30)->nullable(); // attendance, leave, recurring, manual
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entry_lines');
        Schema::dropIfExists('payroll_entries');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_periods');
    }
};
