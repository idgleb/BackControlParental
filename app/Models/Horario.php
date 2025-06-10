<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $fillable = [
        'nombreDeHorario',
        'diasDeSemana',
        'horaInicio',
        'horaFin',
        'isActive',
    ];
}
