<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->unsignedBigInteger('user_id');
            $table->string('name')->nullable();
            $table->text('data_payload')->nullable();
            $table->integer('data_stale_minutes')->nullable();
            $table->string('data_strategy')->nullable();
            $table->string('polling_url')->nullable();
            $table->string('polling_verb')->nullable();
            $table->string('polling_header')->nullable();
            $table->text('markup')->nullable();
            $table->string('blade_view')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('author_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
