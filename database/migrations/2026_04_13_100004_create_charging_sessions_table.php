<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charging_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->integer('total_steps')->default(0);
            $table->float('peak_watts', 8, 4)->default(0);
            $table->float('battery_start', 5, 2)->default(0);
            $table->float('battery_end', 5, 2)->default(0);
            $table->float('capacity_added', 5, 2)->default(0);
            $table->boolean('flagged_overtime')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charging_sessions');
    }
};
