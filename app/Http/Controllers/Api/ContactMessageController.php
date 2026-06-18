<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use Illuminate\Support\Str;
use App\Models\Organization;
use App\Models\ContactMessage;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class ContactMessageController extends Controller
{

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function store(StoreContactMessageRequest $request)
    {

        // Validar que existe la organización
        $organization = Organization::query()
            ->where('slug', $request->organization_slug)
            ->first();

        if (!$organization) {
            return response()->json([
                'ok' => false,
                'message' => 'La página de contacto no está disponible actualmente.'
            ], 403);
        }

        // Validar que este activa
        if (!$organization->isActive()) {
            return response()->json([
                'ok' => false,
                'message' => 'La página de contacto se encuentra temporalmente deshabilitada.'
            ], 403);
        }

        // Validar que tenga contratado Web (pendiente)
        /*$hasWebPlan = $organization->subscriptions()
            ->whereHas('plan', function ($q) {
                $q->where('subsystem_code', 'web');
            })
            ->active()
            ->exists();

        if (!$hasWebPlan) {
            return response()->json([
                'ok' => false,
                'message' => 'Esta organización no tiene habilitado el formulario de contacto.'
            ], 403);
        }*/



        // Guardar mensaje en DB
        $contactMessage = ContactMessage::create([
            'uuid' => Str::uuid(),

            'organization_id' => $organization->id,

            'first_name' => $request->first_name,
            'last_name' => $request->last_name,

            'email' => $request->email,

            'business_name' => $request->business_name,

            'subject' => $request->subject,

            'phone' => $request->phone,

            'services' => $request->services,

            'custom_fields' => $request->custom_fields ?? [],

            'source' => $request->source, // 'source' => 'landing'

            'message' => $request->message,

            'status' => 'new',

            'ip_address' => $request->ip(),
            'user_agent' => substr(
                $request->userAgent() ?? '',
                0,
                255
            ),
        ]);


        // Enviar correo automático de NOTIFICACION INTERNA
        /*try {
            $this->notificationService->trigger(
                'contact_internal_notification',
                [
                    'first_name' => $request->first_name,
                    'last_name'  => $request->last_name,
                    'email'      => $request->email,
                    'organization_name' => $organization->name,
                    'message'    => $request->message,
                    'subject'    => $request->subject,
                    'services'   => $request->services,
                ],
                organization: $organization,
                branch: null,
                //recipient: $organization->email,
                recipient: null,
                //recipientName: $organization->name,
                recipientName: null,
                notifiable: $contactMessage,
                subsystemCode: 'web',
                applyNotificationRecipients: true
            );
        } catch (\Exception $e) {
            Log::error("Error notification internal: " . $e->getMessage());
        }*/

        // Enviar correo automático de respuesta (USUARIO FINAL)
        /*try {
            $this->notificationService->trigger(
                'contact_auto_reply',
                [
                    'first_name' => $request->first_name,
                    'last_name'  => $request->last_name,
                    'organization_name' => $organization->name,
                    'message'    => $request->message,
                    'subject'    => $request->subject,
                    'services'   => $request->services,
                ],
                

                organization: $organization,
                branch: null,
                recipient: $request->email,
                recipientName: $request->first_name,
                notifiable: $contactMessage,
                subsystemCode: 'web',
            );
        } catch (\Exception $e) {
            Log::error("Error notification auto reply: " . $e->getMessage());
        }*/

        $successMessage = $organization->metadata['contact_success_message']
            ?? 'Gracias por contactarnos. Te responderemos pronto.';

        return response()->json([
            'ok' => true,
            'message' => $successMessage
        ], 201);
    }
}
