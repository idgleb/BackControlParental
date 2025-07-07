<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class BlockAppRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el controlador
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:255',
            'scheduled_unblock_at' => 'nullable|date|after:now',
            'daily_limit_minutes' => 'nullable|integer|min:0|max:1440',
        ];
    }
    
    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scheduled_unblock_at.after' => 'La fecha de desbloqueo debe ser futura.',
            'daily_limit_minutes.max' => 'El límite diario no puede exceder 24 horas (1440 minutos).',
        ];
    }
} 