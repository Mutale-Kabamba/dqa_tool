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
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('audit_code', 50)->unique();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('site_name');
            $table->string('auditor_name')->nullable();
            $table->date('audit_date');
            $table->unsignedTinyInteger('period_month'); // 1 - 12
            $table->unsignedTinyInteger('period_quarter'); // 1 - 4
            $table->unsignedSmallInteger('period_year'); // e.g. 2026
            $table->string('period_label', 50); // e.g. "Jan-2026"
            $table->integer('overall_checked')->default(0);
            $table->integer('overall_compliant')->default(0);
            $table->decimal('overall_score', 5, 4)->default(0.0000);
            $table->string('overall_status', 20)->default('RED'); // GREEN, YELLOW, ORANGE, RED
            $table->string('priority_areas', 255)->nullable();
            $table->text('root_cause_notes')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('facility_in_charge')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'period_year', 'period_quarter', 'period_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
