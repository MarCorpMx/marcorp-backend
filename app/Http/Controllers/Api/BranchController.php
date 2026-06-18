<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Services\FeatureService;

use App\Models\Branch;
use App\Models\BranchUserAccess;
use App\Models\StaffMember;
use App\Models\BranchStaff;
use App\Models\Role;
use App\Models\Subsystem;

use App\Http\Requests\BranchRequest;
use App\Http\Resources\BranchResource;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{

    use ResolvesOrganization;

    public function __construct(
        protected FeatureService $featureService
    ) {}

    /**
     * GET /me/branches
     * Listar sucursales de la organización
     */
    public function index(Request $request)
    {
        $org = $this->getOrganization($request);

        if (!$this->featureService->can($org, $request->user()->id, 'citas.branches')) {
            abort(403, 'No tienes acceso a sucursales');
        }

        $branches = Branch::where('organization_id', $org->id)
            ->orderByDesc('is_primary')   // primero primary
            ->orderByDesc('is_active')    // luego activas
            ->orderBy('id')               // orden natural
            ->get();


        return response()->json([
            'data' => BranchResource::collection($branches),
            'meta' => [
                'organization_branches_count' => $branches->count(),
            ],
        ]);
    }

    /**
     * POST /me/branches
     * Crear nueva sucursal
     */
    public function store(BranchRequest $request)
    {
        $org = $this->getOrganization($request);
        $user = $request->user();

        /*
        |----------------------------------------------------------
        | Validamos permisos de acceso
        |----------------------------------------------------------
        */
        if (!$this->featureService->can($org, $request->user()->id, 'citas.branches')) {
            abort(403, 'No tienes acceso a sucursales');
        }

        /*
        |--------------------------------------------------------------------------
        | Obtener data validada
        |--------------------------------------------------------------------------
        */
        $data = $request->validated();

        $data['social_links'] = array_filter([
            'instagram' => data_get($data, 'social_links.instagram'),
            'facebook'  => data_get($data, 'social_links.facebook'),
            'tiktok'    => data_get($data, 'social_links.tiktok'),
            'youtube'   => data_get($data, 'social_links.youtube'),
            'x'         => data_get($data, 'social_links.x'),
        ]);

        unset(
            $data['organization_id'],
            $data['locked_by_plan'],
            $data['is_active']
        );

        /*
        |----------------------------------------------------------
        | Validamos permisos de creación por rol
        |----------------------------------------------------------
        */
        $role = BranchUserAccess::where('user_id', $user->id)
            ->where('organization_id', $org->id)
            ->where('is_active', true)
            ->with('role')
            ->get()
            ->pluck('role.key')
            ->unique()
            ->toArray();

        $allowedRoles = ['root', 'owner'];

        if (!collect($role)->intersect($allowedRoles)->isNotEmpty()) {
            abort(403, 'No tienes permisos para crear sucursales');
        }

        /*
        |----------------------------------------------------------
        | Límite de creación de sucursales por plan
        |----------------------------------------------------------
        */
        $limit = $this->featureService->limit(
            $org,
            $user->id,
            'citas.branches'
        );

        $currentCount = Branch::where('organization_id', $org->id)->count();

        if (!is_null($limit) && $currentCount >= $limit) {
            return response()->json([
                'message' => 'Has alcanzado el límite de sucursales de tu plan'
            ], 422);
        }

        /*
        |----------------------------------------------------------
        | Creamos sucursal
        |----------------------------------------------------------
        */

        $subsystemId = Subsystem::where('key', 'citas')->value('id');

        $ownerRoleId = Role::where('key', 'owner')->value('id');

        return DB::transaction(function () use ($data, $org, $user, $subsystemId, $ownerRoleId) {

            // 1. Staff global
            $staff = StaffMember::firstOrCreate(
                [
                    'organization_id' => $org->id,
                    'user_id' => $user->id,
                ],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => true,
                ]
            );

            // 2. Crear sucursal
            $branch = Branch::create([
                ...$data,
                'organization_id' => $org->id,
                'is_active' => true,
                'timezone' => $data['timezone'] ?? $org->timezone,
                'created_by' => $user->id
                //'is_primary' => $data['is_primary'] ?? false,
            ]);

            // Sucursal primaria única
            if ($branch->is_primary) {
                Branch::where('organization_id', $org->id)
                    ->where('id', '!=', $branch->id)
                    ->update(['is_primary' => false]);
            }

            // 3. Relación staff - sucursal
            BranchStaff::firstOrCreate([
                'branch_id' => $branch->id,
                'staff_member_id' => $staff->id,
            ]);

            // 4. Acceso con staff_member_id
            BranchUserAccess::updateOrCreate(
                [
                    'organization_id' => $org->id,
                    'user_id' => $user->id,
                    'branch_id' => $branch->id,
                    //'subsystem_id' => Subsystem::where('key', 'citas')->first()->id,
                    'subsystem_id' => $subsystemId,
                ],
                [
                    //'role_id' => Role::where('key', 'owner')->first()->id,
                    'role_id' => $ownerRoleId,
                    'staff_member_id' => $staff->id,
                    'is_active' => true,
                ]
            );

            return $branch;
        });

        return (new BranchResource($branch))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /me/branches/{branch}
     * Obtener detalle de una sucursal
     */
    public function show($branch)
    {
        return response()->json([
            'message' => 'Show branch - not implemented yet'
        ]);
    }

    /**
     * PUT /me/branches/{branch}
     * Actualizar sucursal
     */

    // Tenemos que separar la función para activar/desactivar y usar BranchRequest $request
    public function update(BranchRequest $request, Branch $branch)
    {

        $org = $this->getOrganization($request);
        $user = $request->user();

        /*
        |----------------------------------------------------------
        | Validamos permisos de acceso
        |----------------------------------------------------------
        */
        if ($branch->organization_id !== $org->id) {
            abort(404);
        }

        if (!$this->featureService->can($org, $user->id, 'citas.branches')) {
            abort(403, 'No tienes acceso a sucursales');
        }

        if (!$branch->is_active) {
            return response()->json([
                'message' => 'Esta sucursal está desactivada por tu plan actual'
            ], 403);
        }

        $hasAccess = BranchUserAccess::query()
            ->where('organization_id', $org->id)
            ->where('user_id', $user->id)
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'No tienes acceso a esta sucursal');
        }


        $data = $request->validated();

        $data['social_links'] = array_filter([
            'instagram' => data_get($data, 'social_links.instagram'),
            'facebook'  => data_get($data, 'social_links.facebook'),
            'tiktok'    => data_get($data, 'social_links.tiktok'),
            'youtube'   => data_get($data, 'social_links.youtube'),
            'x'         => data_get($data, 'social_links.x'),
        ]);

        $data['updated_by'] = $user->id;

        // Bloquear campo críticos
        unset(
            $data['organization_id'],
            $data['is_primary'],
            $data['locked_by_plan']
        );


        $branch->update($data);

        $branch->refresh();

        return new BranchResource($branch);
    }

    /*
    |--------------------------------------------------------------------------
    | Cambiar Status de la sucursal
    |--------------------------------------------------------------------------
    */
    public function updateStatus(Request $request, int $branchId)
    {
        $org = $this->getOrganization($request);
        $currentBranch = $request->attributes->get('branch');
        $user = $request->user();

        $targetBranch = Branch::findOrFail($branchId);

        /*
        |----------------------------------------------------------
        | Validamos permisos de acceso
        |----------------------------------------------------------
        */
        if ($targetBranch->organization_id !== $org->id) {
            abort(404);
        }

        if (!$this->featureService->can($org, $user->id, 'citas.branches')) {
            abort(403, 'No tienes acceso a sucursales');
        }

        $hasAccess = BranchUserAccess::query()
            ->where('organization_id', $org->id)
            ->where('user_id', $user->id)
            ->where('branch_id', $targetBranch->id)
            ->where('is_active', true)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'No tienes acceso a esta sucursal');
        }

        if (!$currentBranch->is_active) {
            return response()->json([
                'message' => 'Esta sucursal está desactivada por tu plan actual'
            ], 403);
        }

        $isToggle = $request->has('is_active') && count($request->all()) === 1;


        if ($isToggle) {

            // no desactivar si solo tienen una sucursal
            if ($request->has('is_active') && count($request->all()) > 1) {
                return response()->json([
                    'message' => 'Operación inválida'
                ], 422);
            }

            if ($request->boolean('is_active')) {

                $limit = $this->featureService->limit($org, $user->id, 'citas.branches');

                $activeCount = Branch::where('organization_id', $org->id)
                    ->where('is_active', true)
                    ->count();

                // BLOQUEADA POR PLAN
                if ($targetBranch->locked_by_plan) {
                    return response()->json([
                        'message' => 'Esta sucursal está bloqueada por tu plan actual',
                        'meta' => [
                            'reason' => 'locked_by_plan'
                        ]
                    ], 403);
                }


                if (!is_null($limit)) {

                    if (!$targetBranch->is_active && $activeCount >= $limit) {
                        return response()->json([
                            'message' => 'Has alcanzado el límite de sucursales activas de tu plan',
                            'meta' => [
                                'limit' => $limit,
                                'active' => $activeCount
                            ]
                        ], 422);
                    }
                }
            }

            // No permitir desactivar primaria
            if ($targetBranch->is_primary && !$request->boolean('is_active')) {
                return response()->json([
                    'message' => 'No puedes desactivar la sucursal principal'
                ], 422);
            }

            $targetBranch->update([
                'is_active' => $request->boolean('is_active'),
                'updated_by' => $user->id
            ]);

            return response()->json([
                'data' => ['is_active' => (bool) $targetBranch->is_active]
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */
    public function destroy(Request $request, Branch $branchId)
    {

        $organization = $this->getOrganization($request);
        $user = $request->user();
        $branch = $request->attributes->get('branch');

        /*
        |----------------------------------------------------------
        | Validamos permisos de acceso
        |----------------------------------------------------------
        */
        if ($branch->organization_id !== $organization->id) {
            abort(404);
        }

        if (!$this->featureService->can($organization, $user->id, 'citas.branches')) {
            abort(403, 'No tienes acceso a este módulo');
        }


        return response()->json([
            'message' => 'FALTA IMPLEMENTACIÓN'
        ], 400);


        return response()->json([
            'message' =>
            'Sucursal eliminada correctamente'
        ]);
    }
}
