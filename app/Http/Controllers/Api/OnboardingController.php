<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organization;
use App\Services\NotificationService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;

class OnboardingController extends Controller
{
    /**
     * Reenvío público (sin sesión)
     */
    public function resendPublic(Request $request)
    {
        // Honeypot
        if ($request->filled('website')) {
            abort(403, 'Spam detected.');
        }

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $key = 'resend-verification:' . Str::lower($request->email);

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'message' => 'Demasiados intentos. Intenta más tarde.'
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $user = User::where('email', $request->email)->first();

        // Anti-enumeración SIEMPRE
        if (! $user || $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Si el correo existe, se enviará un enlace de verificación.'
            ]);
        }

        $this->sendVerification($user);

        return response()->json([
            'message' => 'Si el correo existe, se enviará un enlace de verificación.'
        ]);
    }

    /**
     * Reenvío autenticado
     */
    public function resendAuthenticated(Request $request)
    {
        $user = $request->user();

        // Ya verificado
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'El correo ya está verificado.'
            ], 400);
        }

        // Cooldown anti spam
        if (
            $user->last_verification_sent_at &&
            now()->diffInSeconds($user->last_verification_sent_at) < 60
        ) {
            return response()->json([
                'message' => 'Espera un momento antes de reenviar.',
                'cooldown' => 60
            ], 429);
        }

        // Guardar timestamp
        $user->update([
            'last_verification_sent_at' => now()
        ]);

        // Enviar correo
        $this->sendVerification($user);

        return response()->json([
            'message' => 'Correo de verificación reenviado.',
            'cooldown' => 60
        ]);
    }


    /**
     * Consulta el estado actual
     */
    public function status(Request $request)
    {
        $user = $request->user();

        $organization = $user->organizations()->first();

        return response()->json([
            'verified' => $user->hasVerifiedEmail(),

            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'onboarding_step' => $organization->onboarding_step,
                'onboarding_completed_at' => $organization->onboarding_completed_at,
            ] : null
        ]);
    }

    /**
     * Avance
     */
    public function advance(Request $request)
    {
        $request->validate([
            'step' => ['required', 'string']
        ]);

        $user = $request->user();
        $organization = $user->organizations()->first();

        if (!$organization) {
            return response()->json(['message' => 'Organization not found'], 404);
        }

        // Validación anti-hackers (IMPORTANTE)
        $allowedTransitions = [
            Organization::ONBOARDING_EMAIL_PENDING => Organization::ONBOARDING_BUSINESS_SETUP,
            Organization::ONBOARDING_SERVICE_CREATED => Organization::ONBOARDING_AVAILABILITY_SET,
            Organization::ONBOARDING_AVAILABILITY_SET => Organization::ONBOARDING_COMPLETED,
        ];

        $current = $organization->onboarding_step;
        $next = $request->step;

        if (($allowedTransitions[$current] ?? null) !== $next) {
            return response()->json([
                'message' => 'Transición inválida'
            ], 403);
        }

        $organization->advanceOnboarding($next);

        return response()->json([
            'message' => 'Onboarding actualizado',
            'step' => $next
        ]);
    }

    /**
     * Completo
     */
    public function complete(Request $request)
    {
        $user = $request->user();
        $organization = $user->organizations()->first();

        // Seguridad: no puede completar si no llegó al final
        if ($organization->onboarding_step !== Organization::ONBOARDING_COMPLETED) {
            return response()->json([
                'message' => 'Onboarding incompleto'
            ], 403);
        }

        // Aquí sí se marca como terminado REAL
        $organization->update([
            'onboarding_completed_at' => now()
        ]);

        return response()->json([
            'message' => 'Onboarding completado',
            'organization' => [
                'id' => $organization->id,
                'onboarding_step' => $organization->onboarding_step,
                'onboarding_completed_at' => $organization->onboarding_completed_at,
            ]
        ]);
    }

    /**
     * Generar y enviar link
     */
    private function sendVerification(User $user): void
    {

        Log::info('entro a funcion para enviar correo');

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        try {
            app(NotificationService::class)->trigger(
                type: 'auth_verify_email',
                data: [
                    'name' => $user->first_name,
                    'verification_url' => $verificationUrl,
                ],
                organization: $user->organizations()->first(),
                recipient: $user->email,
                recipientName: $user->first_name,
                notifiable: $user,
            );
        } catch (\Throwable $e) {
            Log::error('Error enviando verificación', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
