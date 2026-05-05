<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->integer('time_limit')->default(1200)->after('started_at'); // seconds remaining when session starts
            $table->boolean('is_resumed')->default(false)->after('time_limit');
            $table->string('pause_reason')->nullable()->after('is_resumed'); // 'voltage_flat' | null
        });
    }

    public function down(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropColumn(['time_limit', 'is_resumed', 'pause_reason']);
        });
    }
};