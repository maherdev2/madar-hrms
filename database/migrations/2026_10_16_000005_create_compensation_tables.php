<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('salary_basis', 20); // monthly, daily, hourly
            $table->decimal('base_rate', 14, 2);
            $table->char('currency', 3);
            $table->decimal('standard_hours_per_month', 7, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'effective_from']);
            $table->index(['employee_id', 'effective_from', 'effective_to']);
        });

        Schema::create('recurring_pay_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('label');
            $table->string('kind', 20); // allowance or deduction
            $table->decimal('amount', 14, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_pay_components');
        Schema::dropIfExists('employee_compensations');
    }
};
