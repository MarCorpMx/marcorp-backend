<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AppointmentActionService
{
    public function __construct(
        protected AppointmentTokenService $tokenService,
        protected AppointmentService $appointmentService
    ) {}

    public function handle(
        string $token,
        string $ip,
        ?string $userAgent = null
    ): array {

        return DB::transaction(function () use (
            $token,
            $ip,
            $userAgent
        ) {

            $actionToken =
                $this->tokenService->findByToken(
                    $token
                );

            if (!$actionToken) {

                return [
                    'status' => 'invalid_token',
                    'appointment' => null,
                ];
            }

            $validation =
                $this->tokenService->validate(
                    $actionToken
                );

            if ($validation !== 'valid') {

                return [
                    'status' => $validation,
                    'appointment' => $actionToken->appointment,
                ];
            }

            $appointment =
                $actionToken->appointment;

            switch ($actionToken->action) {

                case 'confirm':

                    $this->appointmentService->confirm(
                        $appointment,
                        [],
                        'email'
                    );

                    $status = 'confirmed';

                    break;

                case 'cancel':

                    $this->appointmentService->cancel(
                        $appointment,
                        [],
                        'email'
                    );

                    $status = 'cancelled';

                    break;

                default:

                    return [
                        'status' => 'invalid_token',
                        'appointment' => $appointment,
                    ];
            }

            $this->tokenService->markAsUsed(
                $actionToken,
                $ip,
                $userAgent
            );

            return [
                'status' => $status,

                'appointment' => $appointment->fresh([
                    'client',
                    'organization',
                    'branch',
                    'branchServiceVariant.branchService',
                ]),
            ];
        });
    }
}
