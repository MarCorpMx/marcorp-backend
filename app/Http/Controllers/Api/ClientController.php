<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Models\Client;
use App\Models\Appointment;
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

            ->addSelect([
                'last_appointment_at' => Appointment::select('start_datetime')
                    ->whereColumn('client_id', 'clients.id')
                    ->latest('start_datetime')
                    ->limit(1),

                'last_appointment_status' => Appointment::select('status')
                    ->whereColumn('client_id', 'clients.id')
                    ->latest('start_datetime')
                    ->limit(1),
            ])

            ->withCount('notes')

            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;

                $q->where(function ($sub) use ($search) {
                    $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })

            ->orderByDesc('created_at')
            ->paginate(12);

        return $clients->through(function ($client) {
            return [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'birth_date' => $client->birth_date,
                'last_appointment' => $client->last_appointment_at,
                'notes_count' => $client->notes_count,
                'status' => $this->calculateStatus(
                    $client->last_appointment_at,
                    $client->last_appointment_status
                ),
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | list
    |--------------------------------------------------------------------------
    */
    public function list(Request $request)
    {
        $organization = $this->getOrganization($request);

        return Client::query()
            ->where('organization_id', $organization->id)

            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;

                $q->where(function ($sub) use ($search) {
                    $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })

            ->orderBy('first_name')
            ->limit(100) // límite razonable
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'full_name' => $client->full_name,
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
            'first_name' => 'required|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'email'      => 'required|email|max:150',
            'phone'      => 'nullable|array',
            'birth_date' => 'nullable|date',
        ]);

        $client = Client::create([
            ...$validated,
            'organization_id' => $organization->id,
            'created_by' => $request->user()?->id,
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

        $client->load([
            'appointments' => fn($q) => $q
                ->latest('start_datetime')
                ->with([
                    'serviceVariant:id,name,service_id',
                    'serviceVariant.service:id,name'
                ])
                ->select(
                    'id',
                    'client_id',
                    'start_datetime',
                    'status',
                    'service_variant_id'
                ),

            'notes' => fn($q) => $q
                ->latest()
                ->select('id', 'client_id', 'title', 'content', 'created_at')
        ]);

        $lastAppointment = $client->appointments->first();

        return response()->json([
            'id' => $client->id,
            'name' => $client->full_name,
            'status' => $this->calculateStatus(
                $lastAppointment?->start_datetime,
                $lastAppointment?->status
            ),
            'phone' => $client->phone,
            'email' => $client->email,

            'history' => $client->appointments->map(fn($a) => [
                'date' => $a->start_datetime,
                'service' =>
                $a->serviceVariant?->service?->name
                    ?? $a->serviceVariant?->name,
                'status' => $a->status,
            ]),

            'notes' => $client->notes->map(fn($note) => [
                'date' => $note->created_at,
                'title' => $note->title,
                'content' => $note->content,
            ])
        ]);
    }

    /*public function show(Request $request, Client $client)
    {
        $organization = $this->getOrganization($request);

        abort_if(
            $client->organization_id !== $organization->id,
            403
        );

        $client->loadCount('notes');

        $lastAppointment = $client->appointments()
            ->latest('start_datetime') 
            ->first();

        return [
            'id' => $client->id,
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'full_name' => $client->full_name,
            'email' => $client->email,
            'phone' => $client->phone,
            'birth_date' => $client->birth_date,
            'notes_count' => $client->notes_count,
            'last_appointment' => $lastAppointment?->start_time,
            'status' => $this->calculateStatus(
                $lastAppointment?->start_time,
                $lastAppointment?->status
            ),
        ];
    }*/

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
                    ->where(
                        fn($q) =>
                        $q->where('organization_id', $organization->id)
                    )
                    ->ignore($client->id)
            ],

            'phone' => ['nullable', 'array'],
            'phone.number' => ['nullable', 'string'],
            'phone.e164Number' => ['nullable', 'string'],
            'phone.countryCode' => ['nullable', 'string', 'size:2'],
            'phone.dialCode' => ['nullable', 'string'],

            'birth_date' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
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
    private function calculateStatus($lastAppointmentAt, $lastAppointmentStatus): string
    {
        if (!$lastAppointmentAt) {
            return 'inactivo';
        }

        if ($lastAppointmentStatus === 'cancelled') {
            return 'riesgo';
        }

        if ($lastAppointmentAt < now()->subMonths(3)) {
            return 'inactivo';
        }

        return 'activo';
    }
}
