<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->string('deviceId');
            $table->foreign('deviceId')
                ->references('deviceId')
                ->on('devices')
                ->onDelete('cascade');
            $table->bigInteger('idHorario');
            $table->string('nombreDeHorario');
            $table->json('diasDeSemana');
            $table->string('horaInicio');
            $table->string('horaFin');
            $table->boolean('isActive');
            $table->unique(['deviceId', 'idHorario']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
