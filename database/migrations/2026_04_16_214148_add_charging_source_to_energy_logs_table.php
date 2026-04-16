<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('energy_logs', function (Blueprint $table) {
        $table->string('charging_source')->nullable()->after('is_charging');
    });
}

public function down(): void
{
    Schema::table('energy_logs', function (Blueprint $table) {
        $table->dropColumn('charging_source');
    });
}
};
