<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('intern_id')->unique()->constrained('interns')->cascadeOnDelete();
            $table->foreignUuid('evaluated_by')->constrained('admin_users')->cascadeOnDelete();
            $table->decimal('discipline_score', 5, 2);
            $table->decimal('performance_score', 5, 2);
            $table->decimal('attitude_score', 5, 2);
            $table->decimal('communication_score', 5, 2);
            $table->decimal('total_score', 5, 2);
            $table->char('grade', 1);
            $table->text('comments')->nullable();
            $table->boolean('edited_by_admin')->default(false);
            $table->uuid('last_edited_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
