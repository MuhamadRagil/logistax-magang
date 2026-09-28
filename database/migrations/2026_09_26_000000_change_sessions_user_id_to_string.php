<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The `sessions` table in production was created outside this repo with
 * Laravel's stock stub, where user_id is BIGINT UNSIGNED. AdminUser ids are
 * UUIDs, so after login the session row insert fails under MySQL strict mode;
 * DatabaseSessionHandler swallows that error, the login is never persisted,
 * and the admin gets bounced back to /login.
 *
 * Only the column type changes — existing rows are kept, and the existing
 * user_id index survives the MODIFY. No-op where the table is missing (local
 * dev uses the file session driver) or the column is already a string.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->userIdColumnExists() || $this->userIdIsString()) {
            return;
        }

        Schema::table('sessions', function (Blueprint $table) {
            $table->string('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! $this->userIdColumnExists() || ! $this->userIdIsString()) {
            return;
        }

        // UUIDs can't be converted back to BIGINT under strict mode. user_id is
        // only informational (the login state lives in `payload`), so clearing
        // non-numeric values keeps the rollback from failing.
        DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('user_id', 'not regexp', '^[0-9]+$')
            ->update(['user_id' => null]);

        Schema::table('sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    private function userIdColumnExists(): bool
    {
        return Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id');
    }

    private function userIdIsString(): bool
    {
        return in_array(Schema::getColumnType('sessions', 'user_id'), ['varchar', 'char', 'string', 'text'], true);
    }
};
