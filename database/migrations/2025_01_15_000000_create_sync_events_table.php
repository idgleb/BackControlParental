<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_events', function (Blueprint $table) {
            $table->id();
            $table->string('deviceId');
            $table->string('entity_type'); // 'horario', 'app', 'device'
            $table->string('entity_id'); // ID de la entidad afectada
            $table->string('action'); // 'create', 'update', 'delete'
            $table->json('data')->nullable(); // Datos del cambio
            $table->json('previous_data')->nullable(); // Datos anteriores (para rollback)
            $table->timestamp('synced_at')->nullable(); // Cuándo fue sincronizado
            $table->timestamps();
            
            // Índices para búsquedas eficientes
            $table->index(['deviceId', 'created_at']);
            $table->index(['deviceId', 'entity_type', 'entity_id']);
            $table->index('synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_events');
    }
}; 