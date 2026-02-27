<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Models\Client;
use App\Models\ClientNote;
use Illuminate\Http\Request;

class ClientNoteController extends Controller
{
    use ResolvesOrganization;

    /*
    |--------------------------------------------------------------------------
    | INDEX - Listar notas del cliente
    |--------------------------------------------------------------------------
    */
    public function index(Request $request, Client $client)
    {
        $organization = $this->getOrganization($request);

        // 🔐 Seguridad multi-tenant
        abort_if(
            $client->organization_id !== $organization->id,
            403,
            'Client does not belong to your organization.'
        );

        $notes = ClientNote::query()
            ->where('organization_id', $organization->id)
            ->where('client_id', $client->id)
            ->with('author')
            ->orderByDesc('created_at')
            ->paginate(15);

        return $notes->through(function ($note) {
            return [
                'id' => $note->id,
                'title' => $note->title,
                'content' => $note->content,
                'author' => [
                    'id' => $note->author?->id,
                    'name' => $note->author?->full_name,
                ],
                'is_private' => $note->is_private,
                'created_at' => $note->created_at,
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | STORE - Crear nueva nota
    |--------------------------------------------------------------------------
    */
    public function store(Request $request, Client $client)
    {
        $organization = $this->getOrganization($request);

        // 🔐 Seguridad multi-tenant
        abort_if(
            $client->organization_id !== $organization->id,
            403,
            'Client does not belong to your organization.'
        );

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_private' => ['boolean'],
        ]);

        $note = ClientNote::create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'author_id' => $request->user()->id, // o professional_id si manejas staff separado
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'is_private' => $validated['is_private'] ?? false,
        ]);

        return response()->json([
            'id' => $note->id,
            'title' => $note->title,
            'content' => $note->content,
            'is_private' => $note->is_private,
            'created_at' => $note->created_at,
        ], 201);
    }
}
