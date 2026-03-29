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
        $organization = Organization::where('slug', $request->organization_slug)
            ->active()
            ->firstOrFail();

        // Guardar mensaje en DB
        $contactMessage = ContactMessage::create([
            'uuid' => Str::uuid(),
            'organization_id' => $organization->id,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'subject'    => $request->subject,
            'phone'      => $request->phone,
            'services'   => $request->services,
            'message'    => $request->message,
            'status'     => 'new',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);


        // Enviar correo automático de NOTIFICACION INTERNA
        try {
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
        }

        // Enviar correo automático de respuesta (USUARIO FINAL)
        try {
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
                /*$organization,
                $request->email,
                $request->first_name*/

                organization: $organization,
                recipient: $request->email,
                recipientName: $request->first_name,
                notifiable: $contactMessage,
                subsystemCode: 'web',
            );
        } catch (\Exception $e) {
            Log::error("Error notification auto reply: " . $e->getMessage());
        }

        $successMessage = $organization->metadata['contact_success_message']
            ?? 'Gracias por contactarnos. Te responderemos pronto.';

        return response()->json([
            'ok' => true,
            'message' => $successMessage
        ], 201);
    }
}
