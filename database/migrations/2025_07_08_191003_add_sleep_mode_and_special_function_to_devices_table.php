<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->boolean('sleep_mode_enabled')->default(false);
            $table->time('sleep_mode_from')->nullable();
            $table->time('sleep_mode_to')->nullable();
            $table->string('special_function')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['sleep_mode_enabled', 'sleep_mode_from', 'sleep_mode_to', 'special_function']);
        });
    }
};
