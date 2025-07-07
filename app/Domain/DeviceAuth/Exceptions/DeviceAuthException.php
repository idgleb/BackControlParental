<?php

namespace App\Domain\DeviceAuth\Exceptions;

abstract class DeviceAuthException extends \Exception
{
    public function __construct(
        string $message = "",
        protected string $errorCode = 'DEVICE_AUTH_ERROR',
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}

class DeviceAlreadyExistsException extends DeviceAuthException
{
    public function __construct(string $deviceId)
    {
        parent::__construct(
            "Device with ID {$deviceId} already exists",
            'DEVICE_ALREADY_EXISTS',
            409
        );
    }
}

class DeviceNotFoundException extends DeviceAuthException
{
    public function __construct(string $deviceId)
    {
        parent::__construct(
            "Device with ID {$deviceId} not found",
            'DEVICE_NOT_FOUND',
            404
        );
    }
}

class InvalidVerificationCodeException extends DeviceAuthException
{
    public function __construct()
    {
        parent::__construct(
            "Invalid verification code",
            'INVALID_VERIFICATION_CODE',
            401
        );
    }
}

class VerificationCodeExpiredException extends DeviceAuthException
{
    public function __construct()
    {
        parent::__construct(
            "Verification code has expired",
            'VERIFICATION_CODE_EXPIRED',
            401
        );
    }
}

class DeviceAlreadyVerifiedException extends DeviceAuthException
{
    public function __construct(string $deviceId)
    {
        parent::__construct(
            "Device {$deviceId} is already verified",
            'DEVICE_ALREADY_VERIFIED',
            400
        );
    }
}

class DeviceNotVerifiedException extends DeviceAuthException
{
    public function __construct(string $deviceId)
    {
        parent::__construct(
            "Device {$deviceId} is not verified",
            'DEVICE_NOT_VERIFIED',
            403
        );
    }
}

class DeviceBlockedException extends DeviceAuthException
{
    public function __construct(string $deviceId, string $reason = '')
    {
        $message = "Device {$deviceId} is blocked";
        if ($reason) {
            $message .= ": {$reason}";
        }
        
        parent::__construct(
            $message,
            'DEVICE_BLOCKED',
            403
        );
    }
}

class InvalidDeviceTokenException extends DeviceAuthException
{
    public function __construct()
    {
        parent::__construct(
            "Invalid or expired device token",
            'INVALID_DEVICE_TOKEN',
            401
        );
    }
}

class TooManyVerificationAttemptsException extends DeviceAuthException
{
    public function __construct(int $attempts, int $maxAttempts)
    {
        parent::__construct(
            "Too many verification attempts ({$attempts}/{$maxAttempts})",
            'TOO_MANY_ATTEMPTS',
            429
        );
    }
} 