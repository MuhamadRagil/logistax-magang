<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('mentor_name')->nullable()->after('issued_city');
        });

        DB::table('certificates')->orderBy('id')->each(function ($certificate) {
            $name = DB::table('interns')
                ->join('admin_users', 'admin_users.id', '=', 'interns.mentor_id')
                ->where('interns.id', $certificate->intern_id)
                ->value('admin_users.name');

            if ($name !== null) {
                DB::table('certificates')->where('id', $certificate->id)->update(['mentor_name' => $name]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('mentor_name');
        });
    }
};
