<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('intern_id')->unique()->constrained('interns')->cascadeOnDelete();
            $table->string('certificate_number')->unique();
            $table->date('issued_date');
            $table->string('issued_city')->default('Tangerang');
            $table->string('pdf_url');
            $table->string('pdf_password');
            $table->integer('download_count')->default(0);
            $table->foreignUuid('generated_by')->constrained('admin_users')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
