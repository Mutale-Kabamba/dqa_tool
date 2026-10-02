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
        Schema::table('users', function (Blueprint $table) {
            $table->json('roles')->nullable()->after('password');
            $table->boolean('is_active')->default(true)->after('roles');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('project_officer_id')->nullable()->after('description')->constrained('users')->nullOnDelete();
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->foreignId('auditor_id')->nullable()->after('project_id')->constrained('users')->nullOnDelete();
            $table->foreignId('project_officer_id')->nullable()->after('auditor_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropForeign(['auditor_id']);
            $table->dropForeign(['project_officer_id']);
            $table->dropColumn(['auditor_id', 'project_officer_id']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['project_officer_id']);
            $table->dropColumn(['project_officer_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['roles', 'is_active']);
        });
    }
};
