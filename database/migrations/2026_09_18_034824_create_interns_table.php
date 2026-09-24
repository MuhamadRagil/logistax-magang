<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('intern_account_id')->nullable()->unique()
                ->constrained('intern_accounts')->nullOnDelete();
            $table->string('full_name');
            $table->string('nim')->unique();
            $table->string('institution');
            $table->string('major');
            $table->string('phone')->nullable();
            $table->string('photo_url')->nullable();
            $table->foreignUuid('division_id')->nullable()
                ->constrained('divisions')->nullOnDelete();
            $table->foreignUuid('mentor_id')->nullable()
                ->constrained('admin_users')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('original_end_date')->nullable();
            $table->enum('status', ['pending', 'active', 'completed', 'failed', 'extended', 'rejected'])
                ->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->text('failed_reason')->nullable();
            $table->enum('registered_via', ['self', 'admin']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interns');
    }
};
