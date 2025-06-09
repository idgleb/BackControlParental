<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $fillable = [
        'nombre_de_horario',
        'dias_de_semana',
        'hora_inicio',
        'hora_fin',
        'is_active',
    ];
}
