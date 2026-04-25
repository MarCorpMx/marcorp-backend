<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use App\Models\ServiceVariant;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SubsystemResolver;



class StaffMemberController extends Controller
{
    use ResolvesOrganization;

    public function __construct(
        protected SubsystemResolver $subsystemResolver
    ) {}

    /**
     * Listar staff members de la organización autenticada
     */
    public function index(Request $request): JsonResponse
    {
        $organization = $this->getOrganization($request);
        $user = $request->user();

        $subsystemCode = $request->get('subsystem', 'citas');
        $subsystemId = $this->subsystemResolver->resolve($subsystemCode);

        $branchId = $request->header('X-Branch-Id');

        $role = DB::table('branch_user_access as bua')
            ->join('roles as r', 'r.id', '=', 'bua.role_id')
            ->where('bua.organization_id', $organization->id)
            ->where('bua.user_id', $user->id)
            ->where('bua.subsystem_id', $subsystemId)
            ->where('bua.branch_id', $branchId)
            ->where('bua.is_active', true)
            ->value('r.key');

        if (!$role) {
            return response()->json([
                'data' => []
            ]);
        }

        $query = $organization->staffMembers()
            ->with(['agendaSetting'])
            ->whereHas('branchStaff', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });

        Log::info('Appointments filter', [
            'subsistem' => $request->has('subsystem'),
            'idSub' => $subsystemId,
            'rolecitoDeCanela' => $role
        ]);

        /*
        |--------------------------------------------------------------------------
        | FILTROS DINÁMICOS
        |--------------------------------------------------------------------------
        */

        // SOLO STAFF → su propio registro
        if ($role === 'staff') {
            $query->where('user_id', $user->id);
        }

        // FILTRO: activos
        if ($request->has('active')) {
            $query->where('is_active', (bool) $request->active);
        }

        // FILTRO: públicos
        if ($request->has('public')) {
            $query->where('is_public', (bool) $request->public);
        }

        // FILTRO: excluir rol
        // rombi debemos verificar como obtener el brach id

        $branchId = $request->header('X-Branch-Id');
        if ($request->filled('exclude_role')) {
            $query->whereDoesntHave('user.branchUserAccesses', function ($q) use ($request, $organization, $subsystemId, $branchId) {

                $q->where('organization_id', $organization->id)
                    ->where('subsystem_id', $subsystemId)
                    ->where('branch_id', $branchId)
                    ->where('is_active', true)
                    ->whereHas('role', function ($roleQ) use ($request) {
                        $roleQ->where('key', $request->exclude_role);
                    });
            });
        }
        /*if ($request->filled('exclude_role')) {
            $query->whereDoesntHave('user.subsystemRoles', function ($q) use ($request, $organization, $subsystemId) {
                $q->where('organization_id', $organization->id)
                    ->where('subsystem_id', $subsystemId)
                    ->whereHas('role', function ($roleQ) use ($request) {
                        $roleQ->where('key', $request->exclude_role);
                    });
            });
        }*/

        $staffMembers = $query
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $staffMembers
        ]);
    }

    /**
     * Obtener los servicios del staff
     */
    public function serviceVariants(Request $request, StaffMember $staffMember)
    {
        $organization = $this->getOrganization($request);

        if ($staffMember->organization_id !== $organization->id) {
            abort(403);
        }

        return response()->json([
            'data' => $staffMember->serviceVariants()->pluck('service_variants.id')
        ]);
    }

    /**
     * Sincronizar servicios
     */
    public function syncServiceVariants(Request $request, StaffMember $staffMember)
    {
        $organization = $this->getOrganization($request);

        if ($staffMember->organization_id !== $organization->id) {
            abort(403);
        }

        $validated = $request->validate([
            'service_variant_ids' => ['required', 'array'],
            'service_variant_ids.*' => ['exists:service_variants,id']
        ]);

        // Validar que pertenezcan a la organización
        $validIds = ServiceVariant::whereIn('id', $validated['service_variant_ids'])
            ->whereHas('service', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })
            ->pluck('id')
            ->toArray();

        $staffMember->serviceVariants()->sync($validIds);

        return response()->json([
            'message' => 'Servicios sincronizados correctamente'
        ]);
    }
    


    //////////////////// rombi -> aquí falta validar bien la organizacion

    /**
     * Crear nuevo staff member
     */
    public function store(Request $request): JsonResponse
    {

        $organization = $this->getOrganization($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'active' => ['boolean'],
        ]);

        $this->authorizeRole($request, ['owner', 'admin']);

        $staffMember = $organization->staffMembers()->create([
            ...$validated,
            'active' => $validated['active'] ?? true,
        ]);

        return response()->json($staffMember, 201);
    }

    /**
     * Mostrar un staff member
     */
    public function show(Request $request, StaffMember $staffMember): JsonResponse
    {
        $this->authorizeAccess($request, $staffMember);

        return response()->json(
            $staffMember->load([
                'agendaSetting',
                'schedules',
                'nonWorkingDays'
            ])
        );
    }

    /**
     * Actualizar staff member
     */
    public function update(Request $request, StaffMember $staffMember): JsonResponse
    {
        $this->authorizeAccess($request, $staffMember);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'active' => ['boolean'],
        ]);

        $staffMember->update($validated);

        return response()->json($staffMember);
    }

    /**
     * Eliminar staff member
     */
    public function destroy(Request $request, StaffMember $staffMember): JsonResponse
    {
        $this->authorizeAccess($request, $staffMember);

        $staffMember->delete();

        return response()->json([
            'message' => 'Staff member eliminado correctamente'
        ]);
    }

    /**
     * Seguridad multi-tenant
     */
    private function authorizeAccess(Request $request, StaffMember $staffMember): void
    {
        $organization = $this->getOrganization($request);

        if ($staffMember->organization_id !== $organization->id) {
            abort(403, 'No autorizado.');
        }
    }

    private function authorizeRole(Request $request, array $allowedRoles): void
    {
        $organization = $this->getOrganization($request);
        $user = $request->user();


        $branchId = $request->header('X-Branch-Id');

        $role = DB::table('branch_user_access as bua')
            ->join('roles as r', 'r.id', '=', 'bua.role_id')
            ->where('bua.organization_id', $organization->id)
            ->where('bua.user_id', $user->id)
            ->where('bua.branch_id', $branchId)
            ->where('bua.is_active', true)
            ->value('r.key');

        if (!in_array($role, $allowedRoles)) {
            abort(403, 'No tienes permisos para esta acción.');
        }
    }
}
