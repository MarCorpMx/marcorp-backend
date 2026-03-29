<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppointmentActionToken;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentActionController extends Controller
{

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(Request $request, $token)
    {
        $messages = [
            'confirmed' => 'Todo está listo. Tu cita ha sido confirmada correctamente.',
            'cancelled' => 'La cita fue cancelada correctamente.',
            'expired' => 'Este enlace ya no está disponible.',
            'invalid_token' => 'No se pudo procesar la acción.',
            'already_used' => 'Este enlace ya fue utilizado.',
            'already_confirmed' => 'Esta cita ya había sido confirmada anteriormente.',
            'already_cancelled' => 'Esta cita ya había sido cancelada previamente.',
            'already_completed' => 'Esta cita ya fue atendida.',
            'already_no_show' => 'Esta cita fue marcada como no asistida.',
        ];

        $result = DB::transaction(function () use ($token, $request) {

            $actionToken = AppointmentActionToken::with([
                'appointment.serviceVariant.service',
                'appointment.client',
                'appointment.organization'
            ])
                ->where('token', $token)
                ->lockForUpdate()
                ->first();

            // Token inválido
            if (!$actionToken) {
                return [
                    'status' => 'invalid_token',
                    'appointment' => null
                ];
            }

            $appointment = $actionToken->appointment;

            // Sin cita
            if (!$appointment) {
                return [
                    'status' => 'invalid_token',
                    'appointment' => null
                ];
            }

            // Expirado
            if ($actionToken->expires_at && now()->greaterThan($actionToken->expires_at)) {
                return [
                    'status' => 'expired',
                    'appointment' => $appointment
                ];
            }

            if ($actionToken->revoked_at) {
                return [
                    'status' => 'expired',
                    'appointment' => $appointment
                ];
            }

            // Ya usado
            if ($actionToken->used_at) {
                return [
                    'status' => 'already_used',
                    'appointment' => $appointment
                ];
            }

            // Estados actuales de la cita
            if ($appointment->status === 'confirmed') {
                return [
                    'status' => 'already_confirmed',
                    'appointment' => $appointment
                ];
            }

            if ($appointment->status === 'cancelled') {
                return [
                    'status' => 'already_cancelled',
                    'appointment' => $appointment
                ];
            }

            if ($appointment->status === 'completed') {
                return [
                    'status' => 'already_completed',
                    'appointment' => $appointment
                ];
            }

            if ($appointment->status === 'no_show') {
                return [
                    'status' => 'already_no_show',
                    'appointment' => $appointment
                ];
            }

            // Ejecutar acción
            if ($actionToken->action === 'confirm') {
                $appointment->update(['status' => 'confirmed']);
                $status = 'confirmed';
            } elseif ($actionToken->action === 'cancel') {
                $appointment->update(['status' => 'cancelled']);
                $status = 'cancelled';
            } else {
                return [
                    'status' => 'invalid_token',
                    'appointment' => $appointment
                ];
            }

            // Auditoría
            $actionToken->update([
                'used_at' => now(),
                'used_ip' => $request->ip(),
                'used_user_agent' => $request->userAgent(),
            ]);

            return [
                'status' => $status,
                'appointment' => $appointment->fresh('serviceVariant.service')
            ];
        });


        $status = $result['status'];
        $appointment = $result['appointment'];

        /*Log::info('Appointment action processed', [
            'token' => $token,
            'status' => $status,
            'appointment_id' => $appointment?->id,
            'email' => $appointment?->client->email,
        ]);*/

        /*
        |--------------------------------------------------------------------------
        | SIDE EFFECTS (FUERA DE LA TRANSACCIÓN)
        |--------------------------------------------------------------------------
        */
        if ($appointment) {

            $organization = $appointment->organization;

            // URL pública para gestión de cita del cliente 
            $manageBaseUrl = config('services.booking.front_url') . "/{$organization->slug}/manage";
            $manageUrl = $manageBaseUrl . "?ref={$appointment->reference_code}";

            //Log::info('Manage URL: ' . $manageUrl);

            if ($status === 'confirmed') {
                // TODO: enviar email confirmación cliente
                try {
                    $client = $appointment->client;
                    $variant = $appointment->serviceVariant;

                    $start = $appointment->start_datetime;

                    $this->notificationService->trigger(
                        'appointment_confirmed',
                        [
                            'first_name' => $client->first_name,
                            'organization_name' => $organization->name,
                            'service_name' => $variant->service->name . ' - ' . $variant->name,
                            'date' => $start->format('d/m/Y'),
                            'time' => $start->format('H:i'),
                            'reference_code' => $appointment->reference_code,
                            'manage_url' => $manageUrl,
                        ],
                        organization: $organization,
                        recipient: $client->email,
                        recipientName: $client->first_name,
                        notifiable: $appointment,
                        subsystemCode: 'citas',
                        applyNotificationRecipients: false
                    );
                } catch (\Exception $e) {
                    Log::error("Error sending confirmation email: " . $e->getMessage());
                }
            }

            if ($status === 'cancelled') {
                // TODO: enviar email cancelación cliente
                // TODO: liberar slot si manejas capacidad

                $bookingUrl = config('services.booking.front_url') . "/{$organization->slug}";

                try {
                    $client = $appointment->client;
                    $variant = $appointment->serviceVariant;

                    $start = $appointment->start_datetime;

                    $this->notificationService->trigger(
                        'appointment_cancelled',
                        [
                            'first_name' => $client->first_name,
                            'organization_name' => $organization->name,
                            'service_name' => $variant->service->name . ' - ' . $variant->name,
                            'date' => $start->format('d/m/Y'),
                            'time' => $start->format('H:i'),
                            'reference_code' => $appointment->reference_code,
                            'manage_url' => $manageUrl,
                            'booking_url' => $bookingUrl,
                        ],
                        organization: $organization,
                        recipient: $client->email,
                        recipientName: $client->first_name,
                        notifiable: $appointment,
                        subsystemCode: 'citas',
                        applyNotificationRecipients: false
                    );
                } catch (\Exception $e) {
                    Log::error("Error sending cancellation email: " . $e->getMessage());
                }
            }
        }

        return $this->response($status, $appointment, $messages);
    }

    protected function response($status, $appointment, $messages)
    {
        return response()->json([
            'status' => $status,
            'message' => $messages[$status] ?? 'Acción procesada',
            'appointment' => $appointment ? [
                'date' => optional($appointment->start_datetime)->format('d/m/Y'),
                'time' => optional($appointment->start_datetime)->format('H:i'),
                'service' =>
                optional($appointment->serviceVariant->service)->name
                    . ' - ' .
                    optional($appointment->serviceVariant)->name,
            ] : null
        ]);
    }
}
