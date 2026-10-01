<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
            $table->string('dimension_name')->nullable();
            $table->text('issue_description');
            $table->string('root_cause_category')->default('Staffing');
            $table->text('action_plan');
            $table->string('responsible_person');
            $table->date('due_date');
            $table->string('status')->default('OPEN'); // OPEN, IN_PROGRESS, RESOLVED, OVERDUE
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['audit_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_action_items');
    }
};
