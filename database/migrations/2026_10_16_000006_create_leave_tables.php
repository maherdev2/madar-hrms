<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // annual, sick, unpaid
            $table->string('name');
            $table->boolean('is_paid');
            $table->boolean('requires_document')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('allocated_days', 7, 2)->default(0);
            $table->decimal('carried_days', 7, 2)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'leave_type_id', 'year'], 'leave_entitlements_employee_type_year_unique');
        });

        Schema::create('leave_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('leave_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('requester_role_id')->nullable()->constrained('roles')->restrictOnDelete();
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'priority', 'effective_from', 'effective_to'], 'leave_workflows_resolution_index');
        });

        Schema::create('leave_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_workflow_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order');
            $table->foreignId('approver_role_id')->constrained('roles')->restrictOnDelete();
            $table->string('approver_scope', 30); // department_manager or global
            $table->boolean('allow_self_approval')->default(false);
            $table->timestamps();
            $table->unique(['leave_workflow_id', 'step_order'], 'leave_workflow_steps_order_unique');
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete(); // snapshot at submission
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('leave_workflow_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('current_step_id')->nullable()->constrained('leave_workflow_steps')->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('requested_days', 7, 2);
            $table->text('reason')->nullable();
            $table->string('document_path')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'status', 'starts_on']);
            $table->index(['status', 'current_step_id']);
        });

        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_workflow_step_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['leave_request_id', 'leave_workflow_step_id'], 'leave_approvals_request_step_unique');
        });

        Schema::create('leave_request_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['leave_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_request_events');
        Schema::dropIfExists('leave_approvals');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_workflow_steps');
        Schema::dropIfExists('leave_workflows');
        Schema::dropIfExists('leave_entitlements');
        Schema::dropIfExists('leave_types');
    }
};
