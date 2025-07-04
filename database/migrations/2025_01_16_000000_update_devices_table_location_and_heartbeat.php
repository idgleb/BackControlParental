<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Campos de ubicación
            $table->decimal('latitude', 10, 8)->nullable()->after('batteryLevel');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->timestamp('location_updated_at')->nullable()->after('longitude');
            
            // Campo para mejorar detección online/offline
            $table->timestamp('last_seen')->nullable()->after('location_updated_at');
            $table->integer('ping_interval_seconds')->default(30)->after('last_seen');
            
            // Índice para optimizar consultas de dispositivos online
            $table->index('last_seen');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['last_seen']);
            $table->dropColumn([
                'latitude',
                'longitude', 
                'location_updated_at',
                'last_seen',
                'ping_interval_seconds'
            ]);
        });
    }
}; 