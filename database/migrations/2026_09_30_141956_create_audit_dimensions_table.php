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
        Schema::create('audit_dimensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
            $table->string('dimension_name'); // 'Accuracy', 'Completeness', 'Consistency', 'Timeliness', 'Validity'
            $table->integer('checked_count')->default(0);
            $table->integer('compliant_count')->default(0);
            $table->decimal('score_percentage', 5, 4)->default(0.0000);
            $table->string('status', 20)->default('RED'); // GREEN, YELLOW, ORANGE, RED
            $table->timestamps();

            $table->unique(['audit_id', 'dimension_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_dimensions');
    }
};
