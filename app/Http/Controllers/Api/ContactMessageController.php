<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\ContactMessage;
use Illuminate\Support\Str;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request)
    {
        $organization = Organization::where('slug', $request->organization_slug)
            ->active()
            ->firstOrFail();

        ContactMessage::create([
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

        $successMessage = $organization->metadata['contact_success_message']
        ?? 'Gracias por contactarnos. Te responderemos pronto.';

        return response()->json([
            'ok' => true,
            'message' => $successMessage
        ], 201);
    }
}
