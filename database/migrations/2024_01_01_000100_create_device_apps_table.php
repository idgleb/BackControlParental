<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('device_apps');
        Schema::create('device_apps', function (Blueprint $table) {
            $table->id();
            $table->string('deviceId');
            $table->foreign('deviceId')
                ->references('deviceId')
                ->on('devices')
                ->cascadeOnDelete();
            $table->string('packageName');
            $table->string('appName');
            $table->binary('appIcon')->nullable();
            $table->string('appCategory');
            $table->string('contentRating');
            $table->boolean('isSystemApp');
            $table->unsignedBigInteger('usageTimeToday');
            $table->unsignedBigInteger('timeStempUsageTimeToday');
            $table->string('appStatus');
            $table->integer('dailyUsageLimitMinutes');
            $table->unique(['deviceId', 'packageName']);
            $table->timestamps();
        });

        // Modificar appIcon a MEDIUMBLOB después de crear la tabla
        DB::statement('ALTER TABLE device_apps MODIFY COLUMN appIcon MEDIUMBLOB');
    }

    public function down(): void
    {
        Schema::dropIfExists('device_apps');
    }
};
