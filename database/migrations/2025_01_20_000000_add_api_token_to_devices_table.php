<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('api_token', 80)->unique()->nullable()->after('deviceId');
            $table->string('verification_code', 6)->nullable()->after('api_token');
            $table->timestamp('verification_expires_at')->nullable()->after('verification_code');
            $table->boolean('is_verified')->default(false)->after('verification_expires_at');
            $table->boolean('is_active')->default(true)->after('is_verified');
            $table->integer('failed_attempts')->default(0)->after('is_active');
            $table->timestamp('blocked_until')->nullable()->after('failed_attempts');
            
            // Índices para performance
            $table->index('api_token');
            $table->index('verification_code');
            $table->index(['is_verified', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['is_verified', 'is_active']);
            $table->dropIndex(['verification_code']);
            $table->dropIndex(['api_token']);
            
            $table->dropColumn([
                'api_token',
                'verification_code',
                'verification_expires_at',
                'is_verified',
                'is_active',
                'failed_attempts',
                'blocked_until'
            ]);
        });
    }
}; 