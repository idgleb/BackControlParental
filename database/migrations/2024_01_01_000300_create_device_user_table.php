<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->string('deviceId');
            $table->primary(['user_id', 'deviceId']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('deviceId')->references('deviceId')->on('devices')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_user');
    }
};
