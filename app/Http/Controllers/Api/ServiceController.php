<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\BranchService;
use App\Models\BranchServiceVariant;
use App\Models\BranchServiceVariantStaff;

use App\Models\Organization;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Services\FeatureService;
use Illuminate\Validation\ValidationException;

// Para imágenes
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{

    use ResolvesOrganization;

    public function __construct(
        protected FeatureService $featureService,
    ) {}

    private function authorizeService(Request $request, BranchService $service)
    {
        $organization = $this->getOrganization($request);

        if ($service->organization_id !== $organization->id) {
            abort(403, 'Acceso denegado');
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

        $services = BranchService::query()
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->with([
                'variants' => function ($q) {
                    $q->orderBy('active', 'desc')
                        ->orderBy('sort_order')
                        ->orderBy('id');
                }
            ])
            ->orderBy('active', 'desc')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json($services);
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
        $branch = $request->attributes->get('branch');

        $variants = BranchServiceVariant::query()
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->where('active', true)
            ->whereHas('service', function ($q) {
                $q->where('active', true);
            })
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
                    'max_capacity' => $variant->max_capacity,
                    'mode' => $variant->mode,
                    'includes_material' => $variant->includes_material,
                    'requires_meeting_link' => $variant->requires_meeting_link,
                    'meeting_provider' => $variant->meeting_provider,

                ];
            })
            ->values();

        return response()->json($variants);
    }

    /*
    |--------------------------------------------------------------------------
    | Staff
    |--------------------------------------------------------------------------
    */
    public function staff(Request $request, int $variantId)
    {
        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');


        /*
        |--------------------------------------------------------------------------
        | Buscar variante de la sucursal actual
        |--------------------------------------------------------------------------
        */
        $variant = BranchServiceVariant::query()
            ->where('id', $variantId)
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->where('active', true)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Obtener staff asignado a esta variante
        |--------------------------------------------------------------------------
        */
        $staff = BranchServiceVariantStaff::query()
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->where('branch_service_variant_id', $variant->id)
            ->where('active', true)
            ->with('staffMember:id,name')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->staffMember->id,
                    'name' => $row->staffMember->name,
                ];
            })
            ->values();

        return response()->json($staff);
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */
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

        $servicesLimit = $this->featureService->finalLimit(
            $organization,
            $user->id,
            'citas.services',
            'extra_services'
        );

        $limits = $this->resolveServiceLimits($servicesLimit);
        $variantsPerServiceLimit = $limits['variants_per_service'];

        // saber si se va a crear el servicio en todas las sucursales "activas"
        $createAllBranches = $request->boolean('create_all_branches');

        // Calculamos los totales
        $branchesToCreateCount = $createAllBranches
            ? $organization->branches()
            ->where('is_active', true)
            ->count()
            : 1; // Cuantas sucursales activas

        $currentCount = $organization->services()->count(); // Cuantos servicios existen
        $totalAfterCreation = $currentCount + $branchesToCreateCount; // Cuantos servicios habrá tras la creación


        if (
            $servicesLimit !== null &&
            $totalAfterCreation > $servicesLimit
        ) {

            $remaining = max(0, $servicesLimit - $currentCount);

            return response()->json([
                'message' =>
                "Tu plan permite hasta {$servicesLimit} servicios. Actualmente tienes {$currentCount} y esta acción creará {$branchesToCreateCount} servicio(s). Te quedan {$remaining} espacios disponibles. Puedes mejorar tu plan o adquirir addons para continuar."
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

                'variants.0.name' => 'required|string|max:255',
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

                'create_all_branches' => 'boolean',

                'variants' => 'required|array|min:1',

                'variants.*.name' => 'required|string|max:255',
                'variants.*.description' => 'nullable|string',
                'variants.*.duration_minutes' => 'required|integer|min:1',
                'variants.*.price' => 'nullable|numeric|min:0',
                'variants.*.max_capacity' => 'required|integer|min:1',
                'variants.*.mode' => 'required|in:online,presential,hybrid',
                'variants.*.includes_material' => 'boolean',
                'variants.*.active' => 'boolean',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | LÍMITE DE VARIANTES POR SERVICIO
        |--------------------------------------------------------------------------
        */
        if (!$isOnboarding) {
            if (
                $variantsPerServiceLimit !== null &&
                count($validated['variants']) > $variantsPerServiceLimit
            ) {
                return response()->json([
                    'message' =>
                    "Tu plan permite hasta {$variantsPerServiceLimit} modalidades por servicio. Puedes mejorar tu plan o adquirir addons para ampliar este límite."
                ], 403);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | STAFF ACTUAL (sirve en onboarding)
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
                    'branch_id' => $branchId,
                    'name' => $validated['name'],
                    'description' => null,
                    'active' => true,
                ]);

                $variant = $service->variants()->create([
                    'organization_id' => $organization->id,
                    'branch_id' => $branchId,

                    'name' => $variantData['name'],
                    'description' => $variantData['description'] ?? null,
                    'duration_minutes' => $variantData['duration_minutes'],
                    'price' => $variantData['price'] ?? 0,
                    'max_capacity' => 1,
                    'mode' => $variantData['mode'],
                    'includes_material' => false,
                    'active' => true,
                ]);


                if ($staffId) {
                    BranchServiceVariantStaff::updateOrCreate([
                        'organization_id' => $organization->id,
                        'branch_id' => $branchId,
                        'branch_service_variant_id' => $variant->id,
                        'staff_member_id' => $staffId,
                    ], [
                        'active' => true,
                        'sort_order' => 0,
                    ]);
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
                    'business_niche' => $organization->business_niche,
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

        $targetBranchIds = $createAllBranches
            ? $organization->branches()
            ->where('is_active', true)
            ->pluck('id')
            : collect([$branchId]);

        $services = DB::transaction(function () use (
            $validated,
            $organization,
            $targetBranchIds
        ) {

            $createdServices = collect();

            foreach ($targetBranchIds as $targetBranchId) {

                $service = $organization->services()->create([
                    'branch_id' => $targetBranchId,
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'active' => $validated['active'] ?? true,
                ]);


                $existingNames = []; // Verificar duplicidad de nombres de variantes

                foreach ($validated['variants'] as $index => $variantData) {

                    // Verificar duplicidad de nombres en variantes
                    $normalizedName = mb_strtolower(trim($variantData['name']));

                    if (in_array($normalizedName, $existingNames)) {
                        return response()->json([
                            'message' => "La modalidad '{$variantData['name']}' está duplicada."
                        ], 422);
                    }

                    $existingNames[] = $normalizedName;


                    $service->variants()->create([
                        'organization_id' => $organization->id,
                        'branch_id' => $targetBranchId,

                        'name' => $variantData['name'],
                        'description' => $variantData['description'] ?? null,
                        'duration_minutes' => $variantData['duration_minutes'],
                        'price' => $variantData['price'] ?? null,
                        'max_capacity' => $variantData['max_capacity'],
                        'mode' => $variantData['mode'],
                        'includes_material' => $variantData['includes_material'] ?? false,
                        'active' => $variantData['active'] ?? true,

                        'sort_order' => $index,
                    ]);
                }

                $createdServices->push(
                    $service->load('variants')
                );
            }

            return BranchService::with('variants')
                ->find($service->id);
        });

        return response()->json([
            'message' => $createAllBranches
                ? 'Servicios creados correctamente'
                : 'Servicio creado correctamente',
            'data' => $services
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar servicios/variantes
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, int $serviceId)
    {
        $organization = $this->getOrganization($request);
        $user = $request->user();
        $branch = $request->attributes->get('branch');

        /*
        |--------------------------------------------------------------------------
        | Validamos permisos
        |--------------------------------------------------------------------------
        */
        if (
            !$this->featureService->can(
                $organization,
                $user->id,
                'citas.services'
            )
        ) {
            abort(403, 'No tienes acceso a esta funcionalidad');
        }

        /*
        |--------------------------------------------------------------------------
        | Validar sucursal activa
        |--------------------------------------------------------------------------
        */
        if (!$branch) {
            return response()->json([
                'message' => 'Sucursal no encontrada'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Buscar servicio (seguridad multi-tenant)
        |--------------------------------------------------------------------------
        */
        $service = BranchService::query()
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->with('variants')
            ->findOrFail($serviceId);

        /*
        |--------------------------------------------------------------------------
        | Límites del plan
        |--------------------------------------------------------------------------
        */
        $servicesLimit = $this->featureService->finalLimit(
            $organization,
            $user->id,
            'citas.services',
            'extra_services'
        );

        $limits = $this->resolveServiceLimits($servicesLimit);

        $variantsPerServiceLimit = $limits['variants_per_service'];
        $deletedVariantsLimit = $limits['deleted_variants_limit'];

        /*
        |--------------------------------------------------------------------------
        | Validación
        |--------------------------------------------------------------------------
        */
        $validated = $request->validate([

            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',

            'variants' => 'required|array|min:1',

            'variants.*.id' => 'nullable|integer',

            'variants.*.name' => 'required|string|max:255',
            'variants.*.description' => 'nullable|string',
            'variants.*.duration_minutes' => 'required|integer|min:1',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.max_capacity' => 'required|integer|min:1',
            'variants.*.mode' => 'required|in:online,presential,hybrid',
            'variants.*.includes_material' => 'boolean',
            'variants.*.active' => 'boolean',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validar límite de modalidades
        |--------------------------------------------------------------------------
        */
        if (
            $variantsPerServiceLimit !== null &&
            count($validated['variants']) > $variantsPerServiceLimit
        ) {
            return response()->json([
                'message' =>
                "Tu plan permite hasta {$variantsPerServiceLimit} modalidades por servicio. Puedes mejorar tu plan o adquirir addons para ampliar este límite."
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Validar nombres duplicados
        |--------------------------------------------------------------------------
        */
        $normalizedNames = [];

        foreach ($validated['variants'] as $variantData) {

            $normalizedName = mb_strtolower(
                trim($variantData['name'])
            );

            if (in_array($normalizedName, $normalizedNames)) {

                return response()->json([
                    'message' =>
                    "La modalidad '{$variantData['name']}' está duplicada."
                ], 422);
            }

            $normalizedNames[] = $normalizedName;
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */
        $service = DB::transaction(function () use (
            $validated,
            $service,
            $organization,
            $branch,
            $deletedVariantsLimit
        ) {

            /*
            |--------------------------------------------------------------------------
            | Actualizar servicio
            |--------------------------------------------------------------------------
            */
            $service->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'active' => $validated['active'] ?? true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Variantes actuales
            |--------------------------------------------------------------------------
            */
            $existingVariantIds = $service->variants
                ->pluck('id')
                ->toArray();

            /*
            |--------------------------------------------------------------------------
            | Variantes enviadas
            |--------------------------------------------------------------------------
            */
            $payloadVariantIds = collect($validated['variants'])
                ->pluck('id')
                ->filter()
                ->toArray();

            /*
            |--------------------------------------------------------------------------
            | Variantes eliminadas desde frontend
            |--------------------------------------------------------------------------
            */
            $variantsToDelete = array_diff(
                $existingVariantIds,
                $payloadVariantIds
            );

            /*
            |--------------------------------------------------------------------------
            | Validar límite de eliminadas
            |--------------------------------------------------------------------------
            */
            if (!empty($variantsToDelete)) {

                $currentDeletedCount = BranchServiceVariant::onlyTrashed()
                    ->where('organization_id', $organization->id)
                    ->count();

                $totalAfterDelete =
                    $currentDeletedCount + count($variantsToDelete);

                if (
                    $deletedVariantsLimit !== null &&
                    $totalAfterDelete > $deletedVariantsLimit
                ) {

                    throw ValidationException::withMessages([
                        'variants' => [
                            "Has alcanzado el límite de modalidades eliminadas ({$deletedVariantsLimit}). Restaura algunas modalidades o mejora tu plan para continuar."
                        ]
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Soft delete
                |--------------------------------------------------------------------------
                */
                BranchServiceVariant::query()
                    ->where('organization_id', $organization->id)
                    ->where('branch_id', $branch->id)
                    ->whereIn('id', $variantsToDelete)
                    ->delete();
            }

            /*
            |--------------------------------------------------------------------------
            | Crear / actualizar variantes
            |--------------------------------------------------------------------------
            */
            foreach ($validated['variants'] as $index => $variantData) {

                $variant = null;

                /*
                |--------------------------------------------------------------------------
                | Buscar variante existente
                |--------------------------------------------------------------------------
                */
                if (!empty($variantData['id'])) {

                    $variant = BranchServiceVariant::query()
                        ->where('organization_id', $organization->id)
                        ->where('branch_id', $branch->id)
                        ->where('branch_service_id', $service->id)
                        ->where('id', $variantData['id'])
                        ->first();
                }

                /*
                |--------------------------------------------------------------------------
                | Actualizar variante
                |--------------------------------------------------------------------------
                */
                if ($variant) {

                    $variant->update([
                        'name' => $variantData['name'],
                        'description' => $variantData['description'] ?? null,
                        'duration_minutes' => $variantData['duration_minutes'],
                        'price' => $variantData['price'] ?? null,
                        'max_capacity' => $variantData['max_capacity'],
                        'mode' => $variantData['mode'],
                        'includes_material' => $variantData['includes_material'] ?? false,
                        'active' => $variantData['active'] ?? true,
                        'sort_order' => $index,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Crear nueva variante
                |--------------------------------------------------------------------------
                */ else {

                    $service->variants()->create([

                        'organization_id' => $organization->id,
                        'branch_id' => $branch->id,

                        'name' => $variantData['name'],
                        'description' => $variantData['description'] ?? null,
                        'duration_minutes' => $variantData['duration_minutes'],
                        'price' => $variantData['price'] ?? null,
                        'max_capacity' => $variantData['max_capacity'],
                        'mode' => $variantData['mode'],
                        'includes_material' => $variantData['includes_material'] ?? false,
                        'active' => $variantData['active'] ?? true,
                        'sort_order' => $index,
                    ]);
                }
            }

            return $service->fresh()->load([
                'variants' => function ($q) {
                    $q->orderBy('active', 'desc')
                        ->orderBy('sort_order')
                        ->orderBy('id');
                }
            ]);
        });

        return response()->json([
            'message' => 'Servicio actualizado correctamente',
            'data' => $service
        ]);
    }

    private function resolveServiceLimits(?int $servicesLimit): array
    {
        return match ($servicesLimit) {

            5 => [
                'variants_per_service' => 3,
                'deleted_variants_limit' => 15,
            ],

            10 => [
                'variants_per_service' => 10,
                'deleted_variants_limit' => 50,
            ],

            50 => [
                'variants_per_service' => 30,
                'deleted_variants_limit' => 300,
            ],

            100 => [
                'variants_per_service' => 50,
                'deleted_variants_limit' => 1000,
            ],

            null => [
                'variants_per_service' => null,
                'deleted_variants_limit' => null,
            ],

            default => [
                'variants_per_service' => 3,
                'deleted_variants_limit' => 15,
            ],
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */
    public function show(Request $request, BranchService $service)
    {
        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');

        /*
        |--------------------------------------------------------------------------
        | Autorizar acceso al servicio actual
        |--------------------------------------------------------------------------
        */
        if (
            $service->organization_id !== $organization->id ||
            $service->branch_id !== $branch->id
        ) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Cargar variantes + staff asignado
        |--------------------------------------------------------------------------
        */
        $service->load([
            'variants.staffAssignments.staffMember'
        ]);

        /*
        |--------------------------------------------------------------------------
        | Transformar salida compatible con frontend anterior
        |--------------------------------------------------------------------------
        */
        $service->variants->transform(function ($variant) {

            $variant->staff = $variant->staffAssignments
                ->map(function ($assignment) {
                    return [
                        'id' => $assignment->staffMember->id,
                        'name' => $assignment->staffMember->name,
                    ];
                })
                ->values();

            unset($variant->staffAssignments);

            return $variant;
        });

        return response()->json($service);
    }


    /*
    |--------------------------------------------------------------------------
    | Cambiar Status del servicio
    |--------------------------------------------------------------------------
    */
    public function updateStatus(Request $request, int $serviceId)
    {
        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');


        /*
        |----------------------------------------------------------
        | Validamos permisos de acceso
        |----------------------------------------------------------
        */
        if (!$this->featureService->can($organization, $request->user()->id, 'citas.services')) {
            abort(403, 'No tienes acceso a sucursales');
        }

        $request->validate([
            'active' => ['required', 'boolean']
        ]);

        $service = BranchService::query()
            ->where('id', $serviceId)
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        DB::transaction(function () use ($request, $service) {

            $service->update([
                'active' => $request->boolean('active')
            ]);

            if (!$request->boolean('active')) {
                $service->variants()->update([
                    'active' => false
                ]);
            }
        });

        return response()->json([
            'message' => 'Estado actualizado',
            'data' => [
                'id' => $service->id,
                'active' => $service->fresh()->active
            ]
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cambiar Status de la variante del servicio
    |--------------------------------------------------------------------------
    */
    public function updateVariantStatus(Request $request, int $variantId)
    {
        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');

        /*
        |----------------------------------------------------------
        | Validamos permisos
        |----------------------------------------------------------
        */
        if (!$this->featureService->can($organization, $request->user()->id, 'citas.services')) {
            abort(403, 'No tienes acceso a servicios');
        }

        /*
        |----------------------------------------------------------
        | Validación
        |----------------------------------------------------------
        */
        $request->validate([
            'active' => ['required', 'boolean']
        ]);

        /*
        |----------------------------------------------------------
        | Buscar variante con scope correcto
        |----------------------------------------------------------
        */
        $variant = BranchServiceVariant::query()
            ->where('id', $variantId)
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->with('service')
            ->firstOrFail();

        /*
        |----------------------------------------------------------
        | REGLA CLAVE
        |----------------------------------------------------------
        | No puedes activar una variante si el servicio está inactivo
        */
        if ($request->boolean('active') && !$variant->service->active) {
            return response()->json([
                'message' => 'No puedes activar una modalidad de un servicio inactivo'
            ], 422);
        }

        /*
        |----------------------------------------------------------
        | Actualización
        |----------------------------------------------------------
        */
        DB::transaction(function () use ($request, $variant) {

            $variant->update([
                'active' => $request->boolean('active')
            ]);
        });

        return response()->json([
            'message' => 'Estado de modalidad actualizado',
            'data' => [
                'id' => $variant->id,
                'active' => $variant->fresh()->active
            ]
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Subir imagen
    |--------------------------------------------------------------------------
    */
    public function uploadImage(Request $request, int $variantId)
    {

        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');

        /*
        |----------------------------------------------------------
        | Validamos permisos
        |----------------------------------------------------------
        */
        if (!$this->featureService->can($organization, $request->user()->id, 'citas.services')) {
            abort(403, 'No tienes acceso a esta acción');
        }

        /*
        |----------------------------------------------------------
        | Buscar variante con scope correcto
        |----------------------------------------------------------
        */
        $variant = BranchServiceVariant::query()
            ->where('id', $variantId)
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->with('service')
            ->firstOrFail();

        /*
        |----------------------------------------------------------
        | REGLA CLAVE
        |----------------------------------------------------------
        */
        if (!$variant->service->active) {
            return response()->json([
                'message' => 'No puedes subir imagenes de una modalidad inactiva'
            ], 422);
        }

        /*
        |----------------------------------------------------------
        | Validación
        |----------------------------------------------------------
        */
        $request->validate(
            [
                'image' => [
                    'required',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:5120', // 5 MB
                ]
            ],
            [
                'image.required' => 'Debes seleccionar una imagen.',
                'image.image' => 'El archivo seleccionado no es una imagen válida.',
                'image.mimes' => 'La imagen debe estar en formato JPG, JPEG, PNG o WEBP.',
                'image.max' => 'La imagen no puede superar los 5 MB.',
            ]
        );

        //organizations/{organization_id}/{branch_id}/services/variants/{variant_id}/image.webp

        // Borrar imagen anterior
        if ($variant->image_url) {

            Storage::disk('public')
                ->delete($variant->image_url);
        }

        // Crear manager
        $manager = new ImageManager(
            new Driver()
        );

        // Leer imagen
        $image = $manager->decode(
            $request->file('image')
        );

        // Reducir tamaño máximo
        $image->scaleDown(
            width: 1200,
            height: 1200
        );

        // Convertir a webp
        $encoded = $image->encode(
            new WebpEncoder(
                quality: 80
            )
        );

        // Ruta
        $path =
            'organizations/' .
            $organization->id . '/' .
            $branch->id .
            '/services/variants/' .
            $variant->id .
            '/image.webp';

        // Guardar
        Storage::disk('public')->put(
            $path,
            (string) $encoded
        );

        // Actualizar BD
        $variant->update([
            'image_url' => $path
        ]);

        return response()->json([
            'message' => 'Imagen actualizada',
            'image_url' => $variant->image_url
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar imagen
    |--------------------------------------------------------------------------
    */
    public function deleteImage(Request $request, int $variantId)
    {
        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');

        /*
        |----------------------------------------------------------
        | Validamos permisos
        |----------------------------------------------------------
        */
        if (!$this->featureService->can($organization, $request->user()->id, 'citas.services')) {
            abort(403, 'No tienes acceso a esta acción');
        }

        /*
        |----------------------------------------------------------
        | Buscar variante con scope correcto
        |----------------------------------------------------------
        */
        $variant = BranchServiceVariant::query()
            ->where('id', $variantId)
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->with('service')
            ->firstOrFail();

        /*
        |----------------------------------------------------------
        | REGLA CLAVE
        |----------------------------------------------------------
        */
        if (!$variant->service->active) {
            return response()->json([
                'message' => 'No puedes eliminar imagenes de una modalidad inactiva'
            ], 422);
        }

        $path = $variant->getRawOriginal('image_url');

        if ($path) {

            Storage::disk('public')->delete($path);

            $variant->update([
                'image_url' => null
            ]);
        }

        return response()->json([
            'message' => 'Imagen eliminada'
        ]);
    }
}
