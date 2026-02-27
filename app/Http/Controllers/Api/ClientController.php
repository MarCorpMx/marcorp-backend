<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    use ResolvesOrganization;

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $organization = $this->getOrganization($request);

        $clients = Client::query()
            ->where('organization_id', $organization->id)
            ->withCount('notes')
            ->with(['appointments' => function ($q) {
                $q->latest('start_time')->limit(1);
            }])
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('first_name', 'like', "%{$request->search}%")
                        ->orWhere('last_name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(12);

        return $clients->through(function ($client) {

            $lastAppointment = $client->appointments->first();

            return [
                'id' => $client->id,
                'full_name' => "{$client->first_name} {$client->last_name}",
                'email' => $client->email,
                'phone' => $client->phone,
                'last_appointment' => $lastAppointment?->start_time,
                'notes_count' => $client->notes_count,
                'status' => $this->calculateStatus($lastAppointment),
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $organization = $this->getOrganization($request);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'nullable',
                'email',
                Rule::unique('clients')
                    ->where(fn ($q) =>
                        $q->where('organization_id', $organization->id)
                    )
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
        ]);

        $client = Client::create([
            ...$validated,
            'organization_id' => $organization->id,
        ]);

        return response()->json($client, 201);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */
    public function show(Request $request, Client $client)
    {
        $organization = $this->getOrganization($request);

        abort_if(
            $client->organization_id !== $organization->id,
            403
        );

        $client->loadCount('notes');
        $client->load(['appointments' => function ($q) {
            $q->latest('start_time')->limit(1);
        }]);

        $lastAppointment = $client->appointments->first();

        return [
            'id' => $client->id,
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'email' => $client->email,
            'phone' => $client->phone,
            'birth_date' => $client->birth_date,
            'notes_count' => $client->notes_count,
            'last_appointment' => $lastAppointment?->start_time,
            'status' => $this->calculateStatus($lastAppointment),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Client $client)
    {
        $organization = $this->getOrganization($request);

        abort_if(
            $client->organization_id !== $organization->id,
            403
        );

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => [
                'nullable',
                'email',
                Rule::unique('clients')
                    ->where(fn ($q) =>
                        $q->where('organization_id', $organization->id)
                    )
                    ->ignore($client->id)
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
        ]);

        $client->update($validated);

        return response()->json($client);
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */
    public function destroy(Request $request, Client $client)
    {
        $organization = $this->getOrganization($request);

        abort_if(
            $client->organization_id !== $organization->id,
            403
        );

        $client->delete();

        return response()->json([
            'message' => 'Cliente eliminado correctamente.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS INTELIGENTE
    |--------------------------------------------------------------------------
    */
    private function calculateStatus($lastAppointment)
    {
        if (!$lastAppointment) {
            return 'inactivo';
        }

        if ($lastAppointment->status === 'cancelled') {
            return 'riesgo';
        }

        if ($lastAppointment->start_time < now()->subMonths(3)) {
            return 'inactivo';
        }

        return 'activo';
    }
}