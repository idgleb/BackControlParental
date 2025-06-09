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
            $table->string('package_name')->unique();
            $table->string('app_name');
            $table->string('app_status');
            $table->integer('daily_usage_limit_minutes');
            $table->unsignedBigInteger('usage_time_today');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_apps');
    }
};
