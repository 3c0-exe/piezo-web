<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropForeign(['active_student_id']);
            $table->dropColumn('active_student_id');

            $table->string('active_student_name')->nullable()->after('id');
            $table->string('active_student_email')->nullable()->after('active_student_name');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['active_student_name', 'active_student_email']);

            $table->foreignId('active_student_id')
                ->nullable()
                ->constrained('students')
                ->nullOnDelete()
                ->after('id');
        });
    }
};