<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOrganization;

use App\Models\Client;
use App\Models\Appointment;

use App\Services\FeatureService;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    use ResolvesOrganization;

    public function __construct(
        protected FeatureService $featureService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $organization = $this->getOrganization($request);

        $search = trim((string) $request->search);

        $clients = Client::query()

            ->where(
                'organization_id',
                $organization->id
            )

            /*
            |--------------------------------------------------------------------------
            | Última cita
            |--------------------------------------------------------------------------
            */
            ->addSelect([

                'last_appointment_at' => Appointment::query()
                    ->select('start_datetime')
                    ->whereColumn(
                        'client_id',
                        'clients.id'
                    )
                    ->latest('start_datetime')
                    ->limit(1),

                'last_appointment_status' => Appointment::query()
                    ->select('status')
                    ->whereColumn(
                        'client_id',
                        'clients.id'
                    )
                    ->latest('start_datetime')
                    ->limit(1),

            ])

            /*
            |--------------------------------------------------------------------------
            | Métricas rápidas
            |--------------------------------------------------------------------------
            */
            ->withCount([
                'notes',
                'appointments'
            ])

            /*
            |--------------------------------------------------------------------------
            | Mascotas (solo cuando aplique)
            |--------------------------------------------------------------------------
            */
            ->withCount('pets')

            /*
            |--------------------------------------------------------------------------
            | Búsqueda
            |--------------------------------------------------------------------------
            */
            ->when(
                $search,
                function ($q) use ($search) {

                    $q->where(function ($sub) use ($search) {

                        $sub

                            ->where(
                                'first_name',
                                'like',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'last_name',
                                'like',
                                "%{$search}%"
                            )

                            ->orWhereRaw(
                                "CONCAT(first_name,' ',COALESCE(last_name,'')) LIKE ?",
                                ["%{$search}%"]
                            )

                            ->orWhere(
                                'preferred_name',
                                'like',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'email',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            )

            ->orderByRaw("
                CASE
                    WHEN is_blocked = 0 AND is_active = 1 THEN 1
                    WHEN is_blocked = 0 AND is_active = 0 THEN 2
                    WHEN is_blocked = 1 THEN 3
                    ELSE 4
                END
                ")
            ->latest('created_at')
            ->paginate(12);

        return $clients->through(

            function ($client) {

                return [

                    'id' => $client->id,

                    /*
                    |--------------------------------------------------------------------------
                    | Identidad
                    |--------------------------------------------------------------------------
                    */
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'full_name' => $client->full_name,
                    'preferred_name' => $client->preferred_name,

                    /*
                    |--------------------------------------------------------------------------
                    | Contacto
                    |--------------------------------------------------------------------------
                    */
                    'email' => $client->email,
                    'phone' => $client->phone,

                    /*
                    |--------------------------------------------------------------------------
                    | Personal
                    |--------------------------------------------------------------------------
                    */
                    'birth_date' => $client->birth_date,
                    'gender' => $client->gender,
                    'preferred_language' => $client->preferred_language,
                    'timezone' => $client->timezone,

                    /*
                    |--------------------------------------------------------------------------
                    | CRM
                    |--------------------------------------------------------------------------
                    */
                    'source' => $client->source,
                    'tags' => $client->tags ?? [],
                    'notes' => $client->notes,

                    /*
                    |--------------------------------------------------------------------------
                    | Métricas
                    |--------------------------------------------------------------------------
                    */
                    'appointments_count' => $client->appointments_count,
                    'notes_count' => $client->notes_count,
                    'pets_count' => $client->pets_count,

                    /*
                    |--------------------------------------------------------------------------
                    | Última actividad
                    |--------------------------------------------------------------------------
                    */
                    'last_appointment' => $client->last_appointment_at,

                    /*
                    |--------------------------------------------------------------------------
                    | Estado
                    |--------------------------------------------------------------------------
                    */
                    'status' => $this->calculateStatus(
                        $client->last_appointment_at,
                        $client->last_appointment_status
                    ),

                    'is_active' => $client->is_active,
                    'is_blocked' => $client->is_blocked,
                    'blocked_reason' => $client->blocked_reason,

                ];
            }

        );
    }

    /*
    |--------------------------------------------------------------------------
    | list
    |--------------------------------------------------------------------------
    */
    public function list(Request $request)
    {
        $organization = $this->getOrganization($request);

        $search = trim(
            (string) $request->search
        );

        // No buscar si escribieron menos de 3 caracteres
        if (strlen($search) < 3) {
            return [];
        }

        return Client::query()

            ->where(
                'organization_id',
                $organization->id
            )

            ->where(
                'is_active',
                true
            )

            ->where(
                'is_blocked',
                false
            )

            ->where(function ($q) use ($search) {

                $q

                    ->where(
                        'first_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'last_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhereRaw(
                        "CONCAT(first_name,' ',COALESCE(last_name,'')) LIKE ?",
                        ["%{$search}%"]
                    )

                    ->orWhere(
                        'preferred_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'email',
                        'like',
                        "%{$search}%"
                    );
            })

            ->select([
                'id',
                'first_name',
                'last_name',
                'preferred_name',
                'email',
                'phone',
                'is_active',
                'is_blocked'
            ])

            ->withCount('pets')

            ->orderBy('first_name')

            ->limit(20)

            ->get()

            ->map(fn($client) => [

                'id' => $client->id,

                'full_name' => $client->full_name,

                'preferred_name' => $client->preferred_name,

                'display_name' =>
                $client->preferred_name
                    ?: $client->full_name,

                'email' => $client->email,

                'phone' => $client->phone,

                'pets_count' => $client->pets_count,

                'is_active' => $client->is_active,

                'is_blocked' => $client->is_blocked,
            ]);
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

            /*
            |--------------------------------------------------------------------------
            | Básico
            |--------------------------------------------------------------------------
            */

            'first_name' => [
                'required',
                'string',
                'max:100'
            ],

            'last_name' => [
                'nullable',
                'string',
                'max:100'
            ],

            'preferred_name' => [
                'nullable',
                'string',
                'max:100'
            ],

            /*
            |--------------------------------------------------------------------------
            | Contacto
            |--------------------------------------------------------------------------
            */

            'email' => [
                'nullable',
                'email',
                'max:150'
            ],

            'phone' => [
                'nullable',
                'array'
            ],

            /*'phone.number' => [
                'nullable',
                'string',
                'max:30'
            ],

            'phone.internationalNumber' => [
                'nullable',
                'string',
                'max:50'
            ],

            'phone.nationalNumber' => [
                'nullable',
                'string',
                'max:30'
            ],

            'phone.e164Number' => [
                'nullable',
                'string',
                'max:30'
            ],

            'phone.countryCode' => [
                'nullable',
                'string',
                'size:2'
            ],

            'phone.dialCode' => [
                'nullable',
                'string',
                'max:10'
            ],*/

            /*
            |--------------------------------------------------------------------------
            | Personal
            |--------------------------------------------------------------------------
            */

            'birth_date' => [
                'nullable',
                'date'
            ],

            'gender' => [
                'nullable',
                'string',
                'max:30'
            ],

            'preferred_language' => [
                'nullable',
                'string',
                'max:10'
            ],

            'timezone' => [
                'nullable',
                'string',
                'max:80'
            ],

            /*
            |--------------------------------------------------------------------------
            | CRM
            |--------------------------------------------------------------------------
            */

            'source' => [
                'nullable',
                'string',
                'max:50'
            ],

            'tags' => [
                'nullable',
                'array'
            ],

            'tags.*' => [
                'string',
                'max:50'
            ],

            'notes' => [
                'nullable',
                'string',
                'max:3000'
            ],

            'is_active' => [
                'boolean'
            ],

            /*
            |--------------------------------------------------------------------------
            | Mascota
            |--------------------------------------------------------------------------
            */

            'pets' => [
                'nullable',
                'array'
            ],

            'pets.*.name' => [
                'required_with:pet',
                'string',
                'max:255'
            ],

            'pets.*.species' => [
                'nullable',
                'string',
                'max:50'
            ],

            'pets.*.breed' => [
                'nullable',
                'string',
                'max:100'
            ],

            'pets.*.gender' => [
                'nullable',
                'in:male,female,unknown'
            ],

            'pets.*.weight' => [
                'nullable',
                'numeric'
            ],

            'pets.*.weight_unit' => [
                'nullable',
                'in:kg,lb'
            ],

            'pets.*.color' => [
                'nullable',
                'string',
                'max:100'
            ],

            'pets.*.birth_date' => [
                'nullable',
                'date'
            ],

            'pets.*.allergies' => [
                'nullable',
                'string'
            ],

            'pets.*.medical_notes' => [
                'nullable',
                'string'
            ]
        ]);


        // Normalizar email
        /*$email = !empty($validated['email'])
            ? strtolower(trim($validated['email']))
            : null;*/

        // Evitar email´s duplicados dentro de la organización
        if (!empty($validated['email'])) {

            /*$existingClient = Client::where(
                'organization_id',
                $organization->id
            )
                ->where('email', $validated['email'])
                ->exists();*/

            $existingClientMail = Client::query()
                ->where(
                    'organization_id',
                    $organization->id
                )
                ->where(
                    'email',
                    $validated['email']
                )
                // ->whereNull('deleted_at') // comprobar en soft deletes
                ->first();


            if ($existingClientMail) {
                return response()->json([
                    'message' => 'Ya existe un cliente con ese correo.',
                    'data_existing' => 'email',
                    'existing_client' => [
                        'id' => $existingClientMail->id,
                        'full_name' => $existingClientMail->full_name,
                        'preferred_name' => $existingClientMail->preferred_name,
                        'email' => $existingClientMail->email,
                    ]

                ], 422);
            }
        }

        // Evitar teléfonos duplicados
        $phone = data_get($request->phone, 'e164Number');

        if ($phone) {
            $existingClientPhone = Client::query()
                ->where(
                    'organization_id',
                    $organization->id
                )
                ->where(
                    'phone->e164Number',
                    $phone
                )
                // ->whereNull('deleted_at') // comprobar en soft deletes
                ->first();

            if ($existingClientPhone) {
                return response()->json([
                    'message' =>
                    'El teléfono ya existe y pertenece a "' .
                        $existingClientPhone->display_name .
                        '".',
                    'data_existing' => 'phone',
                    'existing_client' => [
                        'id' => $existingClientPhone->id,
                        'full_name' => $existingClientPhone->full_name,
                        'preferred_name' => $existingClientPhone->preferred_name,
                        'email' => $existingClientPhone->email,
                    ]
                ], 422);
            }
        }


        DB::beginTransaction();

        try {

            $client = Client::create([

                'organization_id' => $organization->id,
                'created_by' => $request->user()?->id,
                'first_name' => trim($validated['first_name']),
                'last_name' => !empty($validated['last_name'])
                    ? trim($validated['last_name'])
                    : null,
                'preferred_name' => !empty($validated['preferred_name'])
                    ? trim($validated['preferred_name'])
                    : null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'preferred_language' => $validated['preferred_language'] ?? 'es',
                'timezone' => $validated['timezone'] ?? null,
                'source' => $validated['source'] ?? 'manual',
                'tags' => $validated['tags'] ?? [],
                'notes' => $validated['notes'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Mascota
            |--------------------------------------------------------------------------
            */

            if (!empty($validated['pets'])) {

                foreach ($validated['pets'] as $pet) {

                    $client->pets()->create([
                        'organization_id' => $organization->id,
                        'name' => trim($pet['name']),
                        'species' => $pet['species'] ?? null,
                        'breed' => $pet['breed'] ?? null,
                        'gender' => $pet['gender'] ?? 'unknown',
                        'weight' => $pet['weight'] ?? null,
                        'weight_unit' => $pet['weight_unit'] ?? 'kg',
                        'color' => $pet['color'] ?? null,
                        'birth_date' => $pet['birth_date'] ?? null,
                        'allergies' => $pet['allergies'] ?? null,
                        'medical_notes' => $pet['medical_notes'] ?? null
                    ]);
                }
            }


            DB::commit();

            return response()->json([
                'message' => 'Cliente creado correctamente',
                'data' => $client->fresh(['pets'])
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'message' => 'No se pudo crear el cliente'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Crear cliente rápido (útil en módulo de citas)
    |--------------------------------------------------------------------------
    */
    public function quickCreate(Request $request)
    {
        $organization = $this->getOrganization($request);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'preferred_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'array'],
            /*solo pet grooming*/
            'pet_name' => ['nullable', 'string'],
            'pet_species' => ['nullable', 'string']
        ]);

        $phone = data_get($request->phone, 'e164Number');

        if ($phone) {

            $existingClient = Client::query()
                ->where(
                    'organization_id',
                    $organization->id
                )
                ->where(
                    'phone->e164Number',
                    $phone
                )
                // ->whereNull('deleted_at') // comprobar en soft deletes
                ->first();

            if ($existingClient) {
                return response()->json([
                    'message' =>
                    'El teléfono ya existe y pertenece a "' .
                        $existingClient->display_name .
                        '".',

                    'existing_client' => [
                        'id' => $existingClient->id,
                        'full_name' => $existingClient->full_name,
                        'preferred_name' => $existingClient->preferred_name,
                        'email' => $existingClient->email,
                        'pets_count' => $existingClient->pets()->count()
                    ]
                ], 422);
            }
        }

        $client =
            Client::create([
                'organization_id' => $organization->id,
                'created_by' => $request->user()?->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'preferred_name' => $data['preferred_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_active' => true
            ]);

        if ($organization->business_niche === 'pet_grooming' && !empty($data['pet_name'])) {

            $client->pets()->create([
                'organization_id' => $organization->id,
                'name' => $data['pet_name'],
                'species' => $data['pet_species']
            ]);
        }

        $client->loadCount('pets');

        return [

            'id' => $client->id,

            'full_name' => $client->full_name,

            'preferred_name' => $client->preferred_name,

            'display_name' => $client->preferred_name
                ?: $client->full_name,

            'email' => $client->email,

            'phone' => $client->phone,

            'pets_count' => $client->pets_count,

            'is_active' => true,

            'is_blocked' => false
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Cambiar Status del cliente
    |--------------------------------------------------------------------------
    */
    public function updateStatus(Request $request, int $clientId)
    {
        $organization = $this->getOrganization($request);

        /*
        |----------------------------------------------------------
        | Validamos permisos de acceso
        |----------------------------------------------------------
        */
        if (!$this->featureService->can($organization, $request->user()->id, 'citas.clients')) {
            abort(403, 'No tienes permisos para realizar la acción solicitada');
        }

        $request->validate([
            'active' => ['required', 'boolean']
        ]);

        $client = Client::query()
            ->where('id', $clientId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();


        $client->update([
            'is_active' => $request->boolean('active'),
            'updated_by' => $request->user()?->id
        ]);

        return response()->json([
            'message' => 'Estado actualizado',
            'data' => [
                'id' => $client->id,
                'is_active' => $client->fresh()->is_active
            ]
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cambiar Bloqueo del cliente
    |--------------------------------------------------------------------------
    */
    public function updateBlock(Request $request, int $clientId)
    {
        $organization = $this->getOrganization($request);

        /*
        |----------------------------------------------------------
        | Validar permisos
        |----------------------------------------------------------
        */
        if (!$this->featureService->can($organization, $request->user()->id, 'citas.clients')) {
            abort(403, 'No tienes permisos para realizar la acción solicitada');
        }

        $request->validate([
            'blocked' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000']
        ]);

        $client = Client::query()
            ->where(
                'id',
                $clientId
            )
            ->where(
                'organization_id',
                $organization->id
            )
            ->firstOrFail();

        DB::transaction(function () use ($request, $client) {

            $blocked = $request->boolean('blocked');

            $client->update([
                'is_blocked' => $blocked,
                'blocked_reason' => $blocked
                    ? $request->reason
                    : null,

                'blocked_by' => $blocked
                    ? $request->user()?->id
                    : null,
                'blocked_at' => $blocked
                    ? now()
                    : null,

                // si se bloquea también desactivar
                'is_active' => $blocked
                    ? false
                    : $client->is_active,

                'updated_by' => $request->user()?->id

            ]);
        });

        return response()->json([
            'message' => 'Estado actualizado',
            'data' => [
                'id' => $client->id,
                'is_blocked' => $client->fresh()->is_blocked,
                'blocked_reason' => $client->fresh()->blocked_reason
            ]

        ]);
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

        $client->loadCount([
            'appointments',
            'notes',
            'pets'
        ]);

        $client->load([

            /*
            |--------------------------------------------------------------------------
            | Mascotas
            |--------------------------------------------------------------------------
            */
            'pets:id,client_id,name,species,breed,gender,weight,weight_unit,color,birth_date,allergies,medical_notes',

            /*
            |--------------------------------------------------------------------------
            | Historial citas
            |--------------------------------------------------------------------------
            */
            'appointments' => fn($q) => $q
                ->latest('start_datetime')
                ->with([
                    'branchServiceVariant:id,name,branch_service_id',
                    'branchServiceVariant.service:id,name'
                ])
                ->select(
                    'id',
                    'client_id',
                    'start_datetime',
                    'status',
                    'branch_service_variant_id'
                ),

            /*
            |--------------------------------------------------------------------------
            | Notas internas
            |--------------------------------------------------------------------------
            */
            'notes' => fn($q) => $q
                ->latest()
                ->select(
                    'id',
                    'client_id',
                    'title',
                    'content',
                    'created_at'
                )

        ]);

        $lastAppointment = Appointment::query()
            ->where('client_id', $client->id)
            ->latest('start_datetime')
            ->first();

        return response()->json([

            'message' => 'Cliente obtenido correctamente',

            'data' => [

                /*
                |--------------------------------------------------------------------------
                | Base ClientApi
                |--------------------------------------------------------------------------
                */

                'id' => $client->id,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'full_name' => $client->full_name,
                'preferred_name' => $client->preferred_name,

                'email' => $client->email,
                'phone' => $client->phone,

                'birth_date' => $client->birth_date,
                'gender' => $client->gender,

                'preferred_language' => $client->preferred_language,
                'timezone' => $client->timezone,

                'source' => $client->source,
                'tags' => $client->tags ?? [],

                // IMPORTANTE:
                // tomar el atributo de BD y no la relación
                'notes' => $client->getAttribute('notes'),

                'is_active' => $client->is_active,
                'is_blocked' => $client->is_blocked,
                'blocked_reason' => $client->blocked_reason,

                'status' => $this->calculateStatus(
                    $lastAppointment?->start_datetime,
                    $lastAppointment?->status
                ),

                /*
                |--------------------------------------------------------------------------
                | Métricas
                |--------------------------------------------------------------------------
                */

                'appointments_count' => $client->appointments_count,
                'notes_count' => $client->notes_count,
                'pets_count' => $client->pets_count,

                'last_appointment' => $lastAppointment?->start_datetime,

                /*
                |--------------------------------------------------------------------------
                | Mascotas
                |--------------------------------------------------------------------------
                */

                'pets' => $client->pets->map(
                    fn($pet) => [

                        'id' => $pet->id,
                        'name' => $pet->name,

                        'species' => $pet->species,
                        'breed' => $pet->breed,

                        'gender' => $pet->gender,

                        'weight' => $pet->weight,
                        'weight_unit' => $pet->weight_unit,

                        'color' => $pet->color,

                        'birth_date' => $pet->birth_date,

                        'allergies' => $pet->allergies,
                        'medical_notes' => $pet->medical_notes
                    ]
                )->values(),

                /*
                |--------------------------------------------------------------------------
                | Historial
                |--------------------------------------------------------------------------
                */

                'history' => $client->appointments->map(
                    fn($a) => [

                        'date' => $a->start_datetime,

                        'service' =>
                        $a->branchServiceVariant?->service?->name
                            ?? $a->branchServiceVariant?->name
                            ?? 'Servicio eliminado',

                        'status' => $a->status,
                    ]
                )->values(),

                /*
                |--------------------------------------------------------------------------
                | Historial notas
                |--------------------------------------------------------------------------
                */

                'notes_history' => $client
                    ->getRelation('notes')
                    ->map(
                        fn($note) => [

                            'date' => $note->created_at,

                            'title' => $note->title,

                            'content' => $note->content
                        ]
                    )
                    ->values()
            ]

        ]);
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

            /*
            |--------------------------------------------------------------------------
            | Cliente
            |--------------------------------------------------------------------------
            */

            'first_name' => [
                'sometimes',
                'string',
                'max:100'
            ],

            'last_name' => [
                'nullable',
                'string',
                'max:100'
            ],

            'preferred_name' => [
                'nullable',
                'string',
                'max:100'
            ],

            'email' => [

                'nullable',
                'email',

                Rule::unique('clients')
                    ->where(
                        fn($q) =>
                        $q->where(
                            'organization_id',
                            $organization->id
                        )
                    )
                    ->ignore($client->id)
            ],

            'phone' => [
                'nullable'
            ],

            'birth_date' => [
                'nullable',
                'date'
            ],

            'gender' => [
                'nullable',
                Rule::in([
                    'female',
                    'male',
                    'non_binary',
                    'other'
                ])
            ],

            'preferred_language' => [
                'nullable',
                'string',
                'max:10'
            ],

            'timezone' => [
                'nullable',
                'string',
                'max:100'
            ],

            'source' => [
                'nullable',
                'string',
                'max:50'
            ],

            'tags' => [
                'nullable',
                'array'
            ],

            'notes' => [
                'nullable',
                'string',
                'max:3000'
            ],

            'is_active' => [
                'sometimes',
                'boolean'
            ],

            /*
            |--------------------------------------------------------------------------
            | Mascotas
            |--------------------------------------------------------------------------
            */

            'pets' => [
                'nullable',
                'array'
            ],

            'pets.*.id' => [
                'nullable',
                'integer'
            ],

            'pets.*.name' => [
                'required_with:pets',
                'string',
                'max:100'
            ],

            'pets.*.species' => [
                'nullable',
                'string',
                'max:100'
            ],

            'pets.*.breed' => [
                'nullable',
                'string',
                'max:100'
            ],

            'pets.*.gender' => [
                'nullable',
                'string'
            ],

            'pets.*.weight' => [
                'nullable',
                'numeric'
            ],

            'pets.*.weight_unit' => [
                'nullable',
                'string'
            ],

            'pets.*.color' => [
                'nullable',
                'string',
                'max:100'
            ],

            'pets.*.birth_date' => [
                'nullable',
                'date'
            ],

            'pets.*.allergies' => [
                'nullable',
                'string'
            ],

            'pets.*.medical_notes' => [
                'nullable',
                'string'
            ]

        ]);

        DB::transaction(function () use (
            $client,
            $validated,
            $organization,
            $request
        ) {

            /*
            |--------------------------------------------------------------------------
            | Cliente
            |--------------------------------------------------------------------------
            */

            $clientData = collect(
                $validated
            )
                ->except('pets')
                ->merge([
                    'updated_by' => $request->user()?->id
                ])
                ->toArray();

            $client->update(
                $clientData
            );

            /*
            |--------------------------------------------------------------------------
            | Mascotas
            |--------------------------------------------------------------------------
            */

            if (
                array_key_exists(
                    'pets',
                    $validated
                )
            ) {

                $petIds = [];

                foreach (
                    $validated['pets'] as $petData
                ) {

                    $petId =
                        $petData['id']
                        ?? null;

                    unset(
                        $petData['id']
                    );

                    if ($petId) {

                        $pet =
                            $client
                            ->pets()
                            ->where(
                                'organization_id',
                                $organization->id
                            )
                            ->where(
                                'id',
                                $petId
                            )
                            ->first();

                        if ($pet) {

                            $pet->update(
                                $petData
                            );

                            $petIds[] =
                                $pet->id;
                        }
                    } else {

                        $pet =
                            $client
                            ->pets()
                            ->create([
                                ...$petData,
                                'organization_id' => $organization->id
                            ]);

                        $petIds[] =
                            $pet->id;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Eliminar mascotas removidas
                |--------------------------------------------------------------------------
                */

                $client
                    ->pets()
                    ->where(
                        'organization_id',
                        $organization->id
                    )
                    ->whereNotIn(
                        'id',
                        $petIds
                    )
                    ->delete();
            }
        });

        $client->load(
            'pets'
        );

        return response()->json([
            'message' =>
            'Cliente actualizado correctamente',
            'data' => $client
        ]);
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

        DB::transaction(function () use (
            $client,
            $organization,
            $request
        ) {

            /*
            |--------------------------------------------------
            | Verificar historial de citas
            |--------------------------------------------------
            */

            $hasAppointments =
                $client->appointments()
                ->where(
                    'organization_id',
                    $organization->id
                )
                ->exists();

            /*
            |--------------------------------------------------
            | Si tiene historial → soft delete
            |--------------------------------------------------
            */

            if ($hasAppointments) {

                $client
                    ->pets()
                    ->delete();

                $client->update([
                    'deleted_by' => $request->user()?->id
                ]);

                $client->delete();

                return;
            }

            /*
            |--------------------------------------------------
            | Sin historial → eliminar mascotas
            |--------------------------------------------------
            */

            $client
                ->pets()
                ->where(
                    'organization_id',
                    $organization->id
                )
                ->forceDelete();

            /*
            |--------------------------------------------------
            | Eliminar cliente completamente
            |--------------------------------------------------
            */

            $client->forceDelete();
        });

        return response()->json([
            'message' =>
            'Cliente eliminado correctamente'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Consultar mascotas de cliente
    |--------------------------------------------------------------------------
    */
    public function pets(
        Request $request,
        Client $client
    ) {
        $organization = $this->getOrganization($request);

        abort_if($client->organization_id !== $organization->id, 403);

        return $client
            ->pets()
            ->select([
                'id',
                'name',
                'species',
                'breed'
            ])
            ->orderBy('name')
            ->get()
            ->map(
                fn($pet) => [
                    'id' => $pet->id,
                    'name' => $pet->name,
                    'species' => $pet->species,
                    'breed' => $pet->breed
                ]
            );
    }

    // STATUS INTELIGENTE
    private function calculateStatus($lastAppointmentAt, $lastAppointmentStatus): string
    {
        if (!$lastAppointmentAt) {
            return 'inactivo'; // Debe ser algo cómo "risk_level" cliente en riego, cliente pouplar etc
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
