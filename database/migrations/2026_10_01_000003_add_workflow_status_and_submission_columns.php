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
        Schema::table('audits', function (Blueprint $table) {
            $table->string('workflow_status', 30)->default('PENDING_ASSIGNMENT')->after('project_officer_id');
            $table->string('data_file_path')->nullable()->after('facility_in_charge');
            $table->text('strengths_notes')->nullable()->after('root_cause_notes');
            $table->text('discrepancies_notes')->nullable()->after('strengths_notes');
            $table->foreignId('certified_by_id')->nullable()->after('recommendations')->constrained('users')->nullOnDelete();
            $table->timestamp('certified_at')->nullable()->after('certified_by_id');
            $table->text('closure_notes')->nullable()->after('certified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropForeign(['certified_by_id']);
            $table->dropColumn([
                'workflow_status',
                'data_file_path',
                'strengths_notes',
                'discrepancies_notes',
                'certified_by_id',
                'certified_at',
                'closure_notes',
            ]);
        });
    }
};
