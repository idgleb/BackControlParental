<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_apps', function (Blueprint $table) {
            $table->id();
            $table->string('packageName')->unique();
            $table->string('appName');
            $table->string('appStatus');
            $table->integer('dailyUsageLimitMinutes');
            $table->unsignedBigInteger('usageTimeToday');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_apps');
    }
};
