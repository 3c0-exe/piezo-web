<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('charging_sessions');

        Schema::create('charging_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('student_name');
            $table->string('student_email');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('total_steps')->default(0);
            $table->float('peak_watts', 8, 4)->default(0);
            $table->float('peak_voltage', 8, 4)->default(0);
            $table->float('battery_start', 5, 2)->nullable();
            $table->float('battery_end', 5, 2)->nullable();
            $table->boolean('flagged_overtime')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charging_sessions');
    }
};