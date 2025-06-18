<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Horario extends Model
{
    protected $fillable = [
        'deviceId',
        'idHorario',
        'nombreDeHorario',
        'diasDeSemana',
        'horaInicio',
        'horaFin',
        'isActive',
    ];

    protected $casts = [
        'diasDeSemana' => 'array',
        'isActive' => 'boolean',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'deviceId', 'deviceId');
    }
}
