<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceVariant;
use App\Models\Organization;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Services\FeatureService;

class ServiceController extends Controller
{

    use ResolvesOrganization;

    public function __construct(
        protected FeatureService $featureService,
    ) {}

    private function authorizeService(Request $request, Service $service)
    {
        $organization = $this->getOrganization($request);

        if ($service->organization_id !== $organization->id) {
            abort(403, 'Unauthorized.');
        }

        return $organization;
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');

        $currentBranchId = $branch->id;

        /*$services = $organization->services()
            ->with(['variants.staff'])
            ->latest()
            ->get();*/

        $services = $organization->services()
            ->with([
                'variants.branchVariants' => function ($q) use ($currentBranchId) {
                    $q->where('branch_id', $currentBranchId);
                }
            ])
            ->latest()
            ->get();

        return response()->json($services);
    }

    /*
    |--------------------------------------------------------------------------
    | myServices
    |--------------------------------------------------------------------------
    */
    public function myServices(Request $request)
    {
        $user = $request->user();
        $organization = $this->getOrganization($request);
        //$branch = $request->attributes->get('branch');
        $branch = $request->attributes->get('branch');

        $branchId = $branch->id;


        // 🔥 obtener staff correcto por sucursal
        $staffId = $organization->staffMembers()
            ->where('user_id', $user->id)
            ->where('branch_id', $branchId)
            ->value('id');

        if (!$staffId) {
            return collect();
        }

        return $organization->services()
            ->whereHas('variants.staff', function ($q) use ($staffId, $branchId) {
                $q->where('staff_member_id', $staffId)
                    ->where('service_variant_staff.branch_id', $branchId);
            })
            ->with(['variants' => function ($q) use ($branchId, $staffId) {
                $q->whereHas('staff', function ($q2) use ($branchId, $staffId) {
                    $q2->where('staff_member_id', $staffId)
                        ->where('service_variant_staff.branch_id', $branchId);
                });
            }])
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | List
    |--------------------------------------------------------------------------
    */
    public function list(Request $request)
    {
        $organization = $this->getOrganization($request);
        //$user = $request->user();

        $servicesQuery = $organization->services()
            ->where('active', true);

        // Si NO es admin
        return $servicesQuery
            ->orderBy('name')
            ->limit(100)
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                ];
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | List Variants
    |--------------------------------------------------------------------------
    */
    public function listVariants(Request $request)
    {

        $organization = $this->getOrganization($request);
        //$user = $request->user();

        $variantsQuery = \App\Models\ServiceVariant::query()
            ->whereHas('service', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id)
                    ->where('active', true);
            })
            ->where('active', true);

        return $variantsQuery
            ->with('service:id,name')
            ->orderBy('name')
            ->limit(100)
            ->get()
            ->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'label' => $variant->service->name . ' - ' . $variant->name,
                    'duration' => $variant->duration_minutes,
                    'price' => $variant->price,
                ];
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Staff
    |--------------------------------------------------------------------------
    */
    public function staff(Request $request, $variantId)
    {
        $organization = $this->getOrganization($request);

        $variant = \App\Models\ServiceVariant::query()
            ->whereHas('service', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })
            ->with('staff:id,name')
            ->findOrFail($variantId);

        return $variant->staff->map(function ($staff) {
            return [
                'id' => $staff->id,
                'name' => $staff->name,
            ];
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */
    /* rombi -> mejora
hacer que cuando un admin cree un servicio se asigne automáticamente a TODO el staff activo (como hacen plataformas tipo agenda profesional).
Eso evita un problema común en sistemas de reservas.*/

    public function store(Request $request)
    {
        $organization = $this->getOrganization($request);
        $user = $request->user();
        $branch = $request->attributes->get('branch');

        /*
        |--------------------------------------------------------------------------
        | Validamos permisos de acceso
        |--------------------------------------------------------------------------
        */
        if (!$this->featureService->can($organization, $user->id, 'citas.services')) {
            abort(403, 'No tienes acceso a esta funcionalidad');
        }

        /*
        |--------------------------------------------------------------------------
        | Límite de servicios
        |--------------------------------------------------------------------------
        */
        $limit = $this->featureService->limit(
            $organization,
            $user->id,
            'citas.services'
        );

        $currentCount = DB::table('services')
            ->where('organization_id', $organization->id)
            ->count();

        if ($limit !== null && $currentCount >= $limit) {
            return response()->json([
                'message' => 'Límite alcanzado. Actualiza tu plan para agregar más servicios.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | GUARD ANTI-BYPASS ONBOARDING
        |--------------------------------------------------------------------------
        */
        if (!$organization->onboarding_completed_at) {

            if (
                $organization->onboarding_step !==
                Organization::ONBOARDING_SERVICE_CREATED
            ) {
                return response()->json([
                    'message' => 'No puedes crear servicios en este momento del onboarding'
                ], 403);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DETECTAR MODO
        |--------------------------------------------------------------------------
        */
        $isOnboarding = !$organization->onboarding_completed_at
            && $organization->onboarding_step === Organization::ONBOARDING_SERVICE_CREATED;

        /*
        |--------------------------------------------------------------------------
        | RESOLVER BRANCH
        |--------------------------------------------------------------------------
        */
        if ($isOnboarding) {

            $branchId = $organization->branches()
                ->where('is_primary', true)
                ->value('id');
        } else {

            if (!$branch) {
                return response()->json([
                    'message' => 'Sucursal no encontrada'
                ], 422);
            }

            $branchId = $branch->id;
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDACIÓN DINÁMICA
        |--------------------------------------------------------------------------
        */
        if ($isOnboarding) {

            $validated = $request->validate([
                'name' => 'required|string|max:255',

                'variants' => 'required|array|min:1',

                'variants.0.duration_minutes' => 'required|integer|min:1',
                'variants.0.price' => 'nullable|numeric|min:0',
                'variants.0.mode' => 'required|in:online,presential,hybrid',
                'variants.0.description' => 'nullable|string',
            ]);

            $variantData = $validated['variants'][0];
        } else {

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'active' => 'boolean',

                'variants' => 'required|array|min:1',

                'variants.*.name' => 'required|string|max:255',
                'variants.*.description' => 'nullable|string',
                'variants.*.duration_minutes' => 'required|integer|min:1',
                'variants.*.price' => 'nullable|numeric|min:0',
                'variants.*.max_capacity' => 'required|integer|min:1',
                'variants.*.mode' => 'required|in:online,presential,hybrid',
                'variants.*.includes_material' => 'boolean',
                'variants.*.active' => 'boolean',

                'variants.*.staff_ids' => 'array',
                'variants.*.staff_ids.*' => 'integer|exists:staff_members,id',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | LÍMITE DE VARIANTES POR SERVICIO
        |--------------------------------------------------------------------------
        */
        if (!$isOnboarding) {

            $maxVariantsPerService = match ($limit) {
                5 => 3,      // FREE
                10 => 10,     // BASIC
                default => 999 // PRO+
            };

            if (count($validated['variants']) > $maxVariantsPerService) {
                return response()->json([
                    'message' => "Tu plan permite máximo {$maxVariantsPerService} variantes por servicio. Mejora tu plan para agregar más opciones."
                ], 403);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | STAFF ACTUAL
        |--------------------------------------------------------------------------
        */
        $staffId = $organization->staffMembers()
            ->where('user_id', $user->id)
            ->value('id');

        /*
        |--------------------------------------------------------------------------
        | ONBOARDING FLOW
        |--------------------------------------------------------------------------
        */
        if ($isOnboarding) {

            $service = DB::transaction(function () use (
                $validated,
                $variantData,
                $organization,
                $branchId,
                $staffId
            ) {

                $service = $organization->services()->create([
                    'name' => $validated['name'],
                    'description' => null,
                    'active' => true,
                ]);

                $variant = $service->variants()->create([
                    'name' => 'Sesión individual',
                    'description' => $variantData['description'] ?? null,
                    'duration_minutes' => $variantData['duration_minutes'],
                    'price' => $variantData['price'] ?? 0,
                    'max_capacity' => 1,
                    'mode' => $variantData['mode'],
                    'includes_material' => false,
                    'active' => true,
                ]);

                DB::table('branch_service_variant')->insert([
                    'organization_id' => $organization->id,
                    'branch_id' => $branchId,
                    'service_variant_id' => $variant->id,

                    'name' => $variant->name,
                    'description' => $variant->description,
                    'duration_minutes' => $variant->duration_minutes,
                    'price' => $variant->price,
                    'max_capacity' => $variant->max_capacity,
                    'mode' => $variant->mode,
                    'includes_material' => $variant->includes_material,
                    'active' => true,

                    'sort_order' => 0,

                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($staffId) {
                    $variant->staff()->syncWithPivotValues(
                        [$staffId],
                        ['branch_id' => $branchId]
                    );
                }

                return $service;
            });

            $organization->advanceOnboarding(
                Organization::ONBOARDING_AVAILABILITY_SET
            );

            return response()->json([
                'message' => 'Servicio creado correctamente',
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'onboarding_step' => $organization->onboarding_step,
                    'onboarding_completed_at' => $organization->onboarding_completed_at,
                ]
            ], 201);
        }

        /*
        |--------------------------------------------------------------------------
        | FLOW NORMAL
        |--------------------------------------------------------------------------
        */
        $service = DB::transaction(function () use (
            $validated,
            $organization,
            $branchId,
            $staffId
        ) {

            $service = $organization->services()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'active' => $validated['active'] ?? true,
            ]);

            foreach ($validated['variants'] as $index => $variantData) {

                $variant = $service->variants()->create([
                    'name' => $variantData['name'],
                    'description' => $variantData['description'] ?? null,
                    'duration_minutes' => $variantData['duration_minutes'],
                    'price' => $variantData['price'] ?? null,
                    'max_capacity' => $variantData['max_capacity'],
                    'mode' => $variantData['mode'],
                    'includes_material' => $variantData['includes_material'] ?? false,
                    'active' => $variantData['active'] ?? true,
                ]);

                DB::table('branch_service_variant')->insert([
                    'organization_id' => $organization->id,
                    'branch_id' => $branchId,
                    'service_variant_id' => $variant->id,

                    'name' => $variant->name,
                    'description' => $variant->description,
                    'duration_minutes' => $variant->duration_minutes,
                    'price' => $variant->price,
                    'max_capacity' => $variant->max_capacity,
                    'mode' => $variant->mode,
                    'includes_material' => $variant->includes_material,
                    'active' => $variant->active,

                    'sort_order' => $index,

                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (!empty($variantData['staff_ids'])) {

                    $validStaffIds = $organization->staffMembers()
                        ->whereIn('id', $variantData['staff_ids'])
                        ->pluck('id')
                        ->toArray();
                } else {

                    $validStaffIds = $staffId ? [$staffId] : [];
                }

                if (!empty($validStaffIds)) {
                    $variant->staff()->syncWithPivotValues(
                        $validStaffIds,
                        ['branch_id' => $branchId]
                    );
                }
            }

            return $service;
        });

        return response()->json([
            'message' => 'Servicio creado correctamente. Puedes ajustar personal y configuración por sucursal.',
            'data' => $service->load(['variants.staff'])
        ], 201);
    }

    public function store_bkp(Request $request)
    {
        $organization = $this->getOrganization($request);
        $user = $request->user();


        /*
        |----------------------------------------------------------
        | GUARD ANTI-BYPASS ONBOARDING
        |----------------------------------------------------------
        */
        if (!$organization->onboarding_completed_at) {

            if ($organization->onboarding_step !== Organization::ONBOARDING_SERVICE_CREATED) {
                return response()->json([
                    'message' => 'No puedes crear servicios en este momento del onboarding'
                ], 403);
            }
        }

        /*
        |----------------------------------------------------------
        | DETECTAR MODO
        |----------------------------------------------------------
        */
        $isOnboarding = !$organization->onboarding_completed_at
            && $organization->onboarding_step === Organization::ONBOARDING_SERVICE_CREATED;

        /*
        |----------------------------------------------------------
        | VALIDACIÓN DINÁMICA
        |----------------------------------------------------------
        */

        if ($isOnboarding) {

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'variants' => 'required|array|min:1',
                'variants.0.duration_minutes' => 'required|integer|min:1',
                'variants.0.price' => 'nullable|numeric|min:0',
                'variants.0.mode' => 'required|in:online,presential,hybrid',
            ]);

            $variantData = $validated['variants'][0];
        } else {

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'active' => 'boolean',

                'branch_id' => 'required|exists:branches,id',

                'variants' => 'required|array|min:1',

                'variants.*.name' => 'required|string|max:255',
                'variants.*.duration_minutes' => 'required|integer|min:1',
                'variants.*.price' => 'nullable|numeric|min:0',
                'variants.*.max_capacity' => 'required|integer|min:1',
                'variants.*.mode' => 'required|in:online,presential,hybrid',
                'variants.*.includes_material' => 'boolean',
                'variants.*.active' => 'boolean',
                'variants.*.staff_ids' => 'array',
                'variants.*.staff_ids.*' => 'integer|exists:staff_members,id',
            ]);
        }

        /*
        |----------------------------------------------------------
        | ONBOARDING FLOW
        |----------------------------------------------------------
        */
        if ($isOnboarding) {

            // Branch default
            $branchId = $organization->branches()
                ->where('is_primary', true)
                ->value('id');

            // Staff del usuario actual
            $staffId = $organization->staffMembers()
                ->where('user_id', $user->id)
                ->value('id');

            $service = DB::transaction(function () use ($validated, $variantData, $organization, $branchId, $staffId) {

                $service = $organization->services()->create([
                    'name' => $validated['name'],
                    'description' => null,
                    'active' => true,
                ]);

                $variant = $service->variants()->create([
                    'name' => 'Sesión individual',
                    'duration_minutes' => $variantData['duration_minutes'],
                    'price' => $variantData['price'] ?? 0,
                    'max_capacity' => 1,
                    'mode' => $variantData['mode'],
                    'includes_material' => false,
                    'active' => true,
                ]);

                if ($staffId && $branchId) {
                    $variant->staff()->syncWithPivotValues(
                        [$staffId],
                        ['branch_id' => $branchId]
                    );
                }

                return $service;
            });

            // AVANZAR ONBOARDING
            $organization->advanceOnboarding(
                Organization::ONBOARDING_AVAILABILITY_SET
            );

            return response()->json([
                'message' => 'Servicio creado correctamente',
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'onboarding_step' => $organization->onboarding_step,
                    'onboarding_completed_at' => $organization->onboarding_completed_at,
                ]
            ], 201);
        }

        /*
        |----------------------------------------------------------
        | FLOW NORMAL (SIN TOCAR)
        |----------------------------------------------------------
        */

        $branchId = $validated['branch_id'];

        $staffId = $organization->staffMembers()
            ->where('user_id', $user->id)
            ->value('id');

        $service = DB::transaction(function () use ($validated, $organization, $staffId, $branchId) {

            $service = $organization->services()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'active' => $validated['active'] ?? true,
            ]);

            foreach ($validated['variants'] as $variantData) {

                $variant = $service->variants()->create([
                    'name' => $variantData['name'],
                    'duration_minutes' => $variantData['duration_minutes'],
                    'price' => $variantData['price'] ?? null,
                    'max_capacity' => $variantData['max_capacity'],
                    'mode' => $variantData['mode'],
                    'includes_material' => $variantData['includes_material'] ?? false,
                    'active' => $variantData['active'] ?? true,
                ]);

                // STAFF IDS
                if (!empty($variantData['staff_ids'])) {

                    $validStaffIds = $organization->staffMembers()
                        ->whereIn('id', $variantData['staff_ids'])
                        ->pluck('id')
                        ->toArray();
                } else {

                    $validStaffIds = $staffId ? [$staffId] : [];
                }

                // SYNC CON BRANCH
                if (!empty($validStaffIds)) {
                    $variant->staff()->syncWithPivotValues(
                        $validStaffIds,
                        ['branch_id' => $branchId]
                    );
                }
            }

            return $service;
        });

        return response()->json(
            $service->load(['variants.staff']),
            201
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(Request $request, Service $service)
    {
        $this->authorizeService($request, $service);

        return response()->json(
            $service->load(['variants.staff'])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Service $service)
    {
        $organization = $this->authorizeService($request, $service);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',

            'variants' => 'sometimes|array|min:1',

            'variants.*.id' => 'nullable|integer|exists:service_variants,id',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.duration_minutes' => 'required|integer|min:1',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.max_capacity' => 'required|integer|min:1',
            'variants.*.mode' => 'required|in:online,presential,hybrid',
            'variants.*.includes_material' => 'boolean',
            'variants.*.active' => 'boolean',

            'variants.*.staff_ids' => 'array',
            'variants.*.staff_ids.*' => 'integer|exists:staff_members,id',
        ]);

        DB::transaction(function () use ($service, $validated, $organization) {

            /*
        |---------------------------------------------------------
        | 1️⃣ actualizar datos base del servicio
        |---------------------------------------------------------
        */

            $service->update([
                'name' => $validated['name'] ?? $service->name,
                'description' => $validated['description'] ?? $service->description,
                'active' => $validated['active'] ?? $service->active,
            ]);

            if (!isset($validated['variants'])) {
                return;
            }

            /*
        |---------------------------------------------------------
        | 2️⃣ obtener variantes actuales
        |---------------------------------------------------------
        */

            $existingVariants = $service->variants()->get()->keyBy('id');

            $receivedIds = [];

            foreach ($validated['variants'] as $variantData) {

                /*
            |---------------------------------------------------------
            | 3️⃣ UPDATE variante existente
            |---------------------------------------------------------
            */

                if (!empty($variantData['id']) && $existingVariants->has($variantData['id'])) {

                    $variant = $existingVariants[$variantData['id']];

                    $variant->update([
                        'name' => $variantData['name'],
                        'duration_minutes' => $variantData['duration_minutes'],
                        'price' => $variantData['price'] ?? null,
                        'max_capacity' => $variantData['max_capacity'],
                        'mode' => $variantData['mode'],
                        'includes_material' => $variantData['includes_material'] ?? false,
                        'active' => $variantData['active'] ?? true,
                    ]);

                    $receivedIds[] = $variant->id;
                } else {

                    /*
                |---------------------------------------------------------
                | 4️⃣ CREATE nueva variante
                |---------------------------------------------------------
                */

                    $variant = $service->variants()->create([
                        'name' => $variantData['name'],
                        'duration_minutes' => $variantData['duration_minutes'],
                        'price' => $variantData['price'] ?? null,
                        'max_capacity' => $variantData['max_capacity'],
                        'mode' => $variantData['mode'],
                        'includes_material' => $variantData['includes_material'] ?? false,
                        'active' => $variantData['active'] ?? true,
                    ]);

                    $receivedIds[] = $variant->id;
                }

                /*
            |---------------------------------------------------------
            | 5️⃣ sincronizar staff
            |---------------------------------------------------------
            */

                if (isset($variantData['staff_ids'])) {

                    $validStaffIds = $organization->staffMembers()
                        ->whereIn('id', $variantData['staff_ids'])
                        ->pluck('id')
                        ->toArray();

                    $variant->staff()->sync($validStaffIds);
                }
            }

            /*
        |---------------------------------------------------------
        | 6️⃣ DESACTIVAR variantes eliminadas (no borrar)
        |---------------------------------------------------------
        */

            $service->variants()
                ->whereNotIn('id', $receivedIds)
                ->update([
                    'active' => false
                ]);
        });

        return response()->json(
            $service->load(['variants.staff'])
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request, Service $service)
    {
        $this->authorizeService($request, $service);

        $service->delete();

        return response()->json([
            'message' => 'Service deleted successfully.'
        ]);
    }
}
