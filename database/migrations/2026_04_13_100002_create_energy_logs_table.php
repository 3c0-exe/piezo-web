<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('steps');
            $table->float('watts', 8, 4);
            $table->float('voltage', 8, 4);
            $table->float('battery_percentage', 5, 2);
            $table->enum('battery_health', ['Good', 'Fair', 'Low', 'Critical']);
            $table->timestamp('logged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_logs');
    }
};
