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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // device_online, device_offline, app_usage, schedule_alert, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Datos adicionales como device_id, app_name, etc.
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            // Índices para mejorar el rendimiento
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
