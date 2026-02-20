<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use Illuminate\Support\Str;
use App\Models\Organization;
use App\Models\ContactMessage;
use App\Services\OrganizationMailService;
use Illuminate\Support\Facades\Log;

class ContactMessageController extends Controller
{
    protected $mailService;

    public function __construct(OrganizationMailService $mailService)
    {
        $this->mailService = $mailService;
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


        // Enviar correo automático de NOTIFICACION INTERNO
        try {
            $this->mailService->sendTemplate(
                $organization,
                'contact_internal_notification', // plantilla activa en organization_mail_templates
                null,      // remitente recibe la respuesta (colocamos null para que tomé los valores de la tabla organization_notification_settings)
                [
                    'first_name' => $request->first_name,
                    'last_name'  => $request->last_name,
                    'email'      => $request->email,
                    'organization_name' => $organization->name,
                    'message'    => $request->message,
                    'subject'    => $request->subject,
                    'services'   => $request->services,
                ],
                true
            );
        } catch (\Exception $e) {
            Log::error("Error enviando correo automático: " . $e->getMessage());
        }

        // Enviar correo automático de respuesta (USUARIO FINAL)
        try {
            $this->mailService->sendTemplate(
                $organization,
                'contact_auto_reply', // plantilla activa en organization_mail_templates
                $request->email,      // remitente recibe la respuesta
                [
                    'first_name' => $request->first_name,
                    'last_name'  => $request->last_name,
                    'organization_name' => $organization->name,
                    'message'    => $request->message,
                    'subject'    => $request->subject,
                    'services'   => $request->services,
                ]
            );
        } catch (\Exception $e) {
            Log::error("Error enviando correo automático: " . $e->getMessage());
        }

        $successMessage = $organization->metadata['contact_success_message']
            ?? 'Gracias por contactarnos. Te responderemos pronto.';

        return response()->json([
            'ok' => true,
            'message' => $successMessage
        ], 201);
    }
}
