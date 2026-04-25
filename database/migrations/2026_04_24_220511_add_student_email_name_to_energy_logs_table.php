<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('energy_logs', function (Blueprint $table) {
            $table->string('student_email')->nullable()->after('id');
            $table->string('student_name')->nullable()->after('student_email');
            $table->dropForeign(['student_id']);
            $table->dropColumn('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('energy_logs', function (Blueprint $table) {
            $table->dropColumn(['student_email', 'student_name']);
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete()->after('id');
        });
    }
};