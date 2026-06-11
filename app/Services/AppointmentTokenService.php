<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentActionToken;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AppointmentTokenService
{
    /*
    |--------------------------------------------------------------------------
    | GENERAR TOKENS
    |--------------------------------------------------------------------------
    */
    public function generate(
        Appointment $appointment
    ): array {

        $this->revoke($appointment);

        $tokens = [];

        foreach (['confirm', 'cancel'] as $action) {

            $tokens[$action] =
                AppointmentActionToken::create([
                    'appointment_id' => $appointment->id,
                    'token' => Str::uuid(),
                    'action' => $action,
                    'expires_at' => now()->addHours(24),
                ]);
        }

        return $tokens;
    }

    /*
    |--------------------------------------------------------------------------
    | REVOCAR TOKENS
    |--------------------------------------------------------------------------
    */
    public function revoke(
        Appointment $appointment,
        ?int $exceptTokenId = null
    ): void {

        AppointmentActionToken::where(
            'appointment_id',
            $appointment->id
        )
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->when(
                $exceptTokenId,
                fn($q) =>
                $q->where(
                    'id',
                    '!=',
                    $exceptTokenId
                )
            )
            ->update([
                'revoked_at' => now()
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSCAR TOKEN
    |--------------------------------------------------------------------------
    */
    public function findByToken(
        string $token
    ): ?AppointmentActionToken {

        return AppointmentActionToken::with([
            'appointment.client',
            'appointment.organization',
            'appointment.branch',
            'appointment.branchServiceVariant.branchService',
        ])
            ->where('token', $token)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDAR TOKEN
    |--------------------------------------------------------------------------
    */
    public function validate(
        AppointmentActionToken $token
    ): string {

        if (!$token->appointment) {
            return 'invalid_token';
        }

        if (
            $token->expires_at &&
            now()->greaterThan($token->expires_at)
        ) {
            return 'expired';
        }

        if ($token->revoked_at) {
            return 'expired';
        }

        if ($token->used_at) {
            return 'already_used';
        }

        $status =
            $token->appointment->status;

        if (
            in_array(
                $status,
                [
                    'confirmed',
                    'cancelled',
                    'completed',
                    'no_show'
                ]
            )
        ) {
            return 'already_' . $status;
        }

        return 'valid';
    }

    /*
    |--------------------------------------------------------------------------
    | MARCAR COMO USADO
    |--------------------------------------------------------------------------
    */
    public function markAsUsed(
        AppointmentActionToken $token,
        string $ip,
        ?string $userAgent = null
    ): void {

        $token->update([
            'used_at' => now(),
            'used_ip' => $ip,
            'used_user_agent' => $userAgent,
        ]);
    }
}
