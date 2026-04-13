<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('event_type', [
                'tracking_started',
                'tracking_stopped',
                'student_assigned',
                'esp32_connected',
                'session_completed',
                'session_overtime',
                'data_received',
            ]);
            $table->text('description');
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};
