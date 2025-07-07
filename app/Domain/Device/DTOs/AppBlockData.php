<?php

namespace App\Domain\Device\DTOs;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;

class AppBlockData implements Arrayable
{
    public function __construct(
        public readonly ?string $reason = null,
        public readonly ?Carbon $scheduledUnblockAt = null,
        public readonly ?int $blockedBy = null,
        public readonly ?int $dailyLimitMinutes = null,
        public readonly bool $notifyChild = true,
        public readonly bool $allowEmergencyAccess = false,
    ) {}
    
    /**
     * Crear desde un Request
     */
    public static function fromRequest(array $data, ?int $userId = null): self
    {
        return new self(
            reason: $data['reason'] ?? 'Bloqueado por control parental',
            scheduledUnblockAt: isset($data['scheduled_unblock_at']) 
                ? Carbon::parse($data['scheduled_unblock_at']) 
                : null,
            blockedBy: $userId,
            dailyLimitMinutes: $data['daily_limit_minutes'] ?? null,
            notifyChild: $data['notify_child'] ?? true,
            allowEmergencyAccess: $data['allow_emergency'] ?? false,
        );
    }
    
    /**
     * Validar los datos
     */
    public function validate(): array
    {
        $errors = [];
        
        if ($this->scheduledUnblockAt && $this->scheduledUnblockAt->isPast()) {
            $errors[] = 'La fecha de desbloqueo debe ser futura';
        }
        
        if ($this->reason && strlen($this->reason) > 255) {
            $errors[] = 'La razón no puede exceder 255 caracteres';
        }
        
        if ($this->dailyLimitMinutes !== null && ($this->dailyLimitMinutes < 0 || $this->dailyLimitMinutes > 1440)) {
            $errors[] = 'El límite diario debe estar entre 0 y 1440 minutos';
        }
        
        return $errors;
    }
    
    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'reason' => $this->reason,
            'scheduled_unblock_at' => $this->scheduledUnblockAt?->toISOString(),
            'blocked_by' => $this->blockedBy,
            'daily_limit_minutes' => $this->dailyLimitMinutes,
            'notify_child' => $this->notifyChild,
            'allow_emergency_access' => $this->allowEmergencyAccess,
        ];
    }
} 