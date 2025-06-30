<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncEvent extends Model
{
    protected $fillable = [
        'deviceId',
        'entity_type',
        'entity_id',
        'action',
        'data',
        'previous_data',
        'synced_at'
    ];

    protected $casts = [
        'data' => 'array',
        'previous_data' => 'array',
        'synced_at' => 'datetime'
    ];

    /**
     * Relación con el dispositivo
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'deviceId', 'deviceId');
    }

    /**
     * Crear evento de creación
     */
    public static function recordCreate(string $deviceId, string $entityType, string $entityId, array $data): self
    {
        return self::create([
            'deviceId' => $deviceId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => 'create',
            'data' => $data
        ]);
    }

    /**
     * Crear evento de actualización
     */
    public static function recordUpdate(string $deviceId, string $entityType, string $entityId, array $newData, array $oldData): self
    {
        return self::create([
            'deviceId' => $deviceId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => 'update',
            'data' => $newData,
            'previous_data' => $oldData
        ]);
    }

    /**
     * Crear evento de eliminación
     */
    public static function recordDelete(string $deviceId, string $entityType, string $entityId, array $oldData): self
    {
        return self::create([
            'deviceId' => $deviceId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => 'delete',
            'previous_data' => $oldData
        ]);
    }

    /**
     * Marcar como sincronizado
     */
    public function markAsSynced(): void
    {
        $this->update(['synced_at' => now()]);
    }

    /**
     * Scope para eventos no sincronizados
     */
    public function scopeUnsynced($query)
    {
        return $query->whereNull('synced_at');
    }

    /**
     * Scope para eventos de un dispositivo
     */
    public function scopeForDevice($query, string $deviceId)
    {
        return $query->where('deviceId', $deviceId);
    }
} 