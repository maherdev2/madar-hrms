<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('frequency', 20); // quarterly, annual
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamps();
            $table->unique(['frequency', 'starts_on', 'ends_on']);
        });

        Schema::create('review_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label');
            $table->unsignedTinyInteger('max_score')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('reviewer_employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('status', 20)->default('draft'); // draft, submitted, finalized
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['review_period_id', 'employee_id']);
        });

        Schema::create('performance_review_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('review_criterion_id')->constrained('review_criteria')->restrictOnDelete();
            $table->unsignedTinyInteger('score');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['performance_review_id', 'review_criterion_id'], 'performance_review_criterion_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_review_scores');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('review_criteria');
        Schema::dropIfExists('review_periods');
    }
};
