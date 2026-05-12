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
     * Listar staff members del brach, lo usamos en: 
     * Configuración > Horario de atención 
     */
    public function index(Request $request): JsonResponse
    {
        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');
        $subsystem = $request->attributes->get('subsystem');

        $user = $request->user();

        $subsystemId = $subsystem->id;
        $branchId = $branch->id;



        /*
        |------------------------------------------------------------------
        | Resolver rol actual del usuario en branch/subsystem
        |------------------------------------------------------------------
        */
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

        /*
        |------------------------------------------------------------------
        | Base query:
        | staff de la organización asignados a la branch actual
        |------------------------------------------------------------------
        */
        $query = $organization->staffMembers()
            ->whereExists(function ($q) use ($branchId) {
                $q->select(DB::raw(1))
                    ->from('branch_staff as bs')
                    ->whereColumn('bs.staff_member_id', 'staff_members.id')
                    ->where('bs.branch_id', $branchId);
            });

        /*
        |------------------------------------------------------------------
        | STAFF solo puede verse a sí mismo
        |------------------------------------------------------------------
        */
        if ($role === 'staff') {
            $query->where('user_id', $user->id);
        }

        /*
        |------------------------------------------------------------------
        | Filtro active (dinámico)
        | default = solo activos
        |------------------------------------------------------------------
        */
        if ($request->has('active')) {
            $query->where('is_active', (bool) $request->active);
        } else {
            $query->where('is_active', true);
        }

        /*
        |------------------------------------------------------------------
        | Filtro public
        |------------------------------------------------------------------
        */
        if ($request->has('public')) {
            $query->where('is_public', (bool) $request->public);
        }

        /*
        |------------------------------------------------------------------
        | Resultado
        |------------------------------------------------------------------
        */
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
        $branch = $request->attributes->get('branch');

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        */
        if ($staffMember->organization_id !== $organization->id) {
            abort(403);
        }

        /*
        |--------------------------------------------------------------------------
        | Solo variantes asignadas en ESTA sucursal
        | Tabla nueva: branch_service_variant_staff
        |--------------------------------------------------------------------------
        */
        $ids = $staffMember->serviceVariants()
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->where('active', true)
            ->pluck('branch_service_variant_id');

        return response()->json([
            'data' => $ids->values()
        ]);
    }

    /**
     * Se usa para actualizar los servicios del staff
     */
    public function syncServiceVariants(Request $request, StaffMember $staffMember)
    {
        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        */
        if ($staffMember->organization_id !== $organization->id) {
            abort(403);
        }

        $validated = $request->validate([
            'service_variant_ids' => ['required', 'array'],
            'service_variant_ids.*' => ['exists:branch_service_variant,id'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validar que las variantes pertenezcan a la organización y sucursal actual
        |--------------------------------------------------------------------------
        */
        $validIds = \App\Models\BranchServiceVariant::query()
            ->whereIn('id', $validated['service_variant_ids'])
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->pluck('id')
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Borrar SOLO asignaciones de esta sucursal
        |--------------------------------------------------------------------------
        */
        \App\Models\BranchServiceVariantStaff::query()
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->where('staff_member_id', $staffMember->id)
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | Insertar nuevas asignaciones
        |--------------------------------------------------------------------------
        */
        $rows = collect($validIds)->map(function ($id) use ($organization, $branch, $staffMember) {
            return [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'branch_service_variant_id' => $id,
                'staff_member_id' => $staffMember->id,
                'active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        if (!empty($rows)) {
            \App\Models\BranchServiceVariantStaff::insert($rows);
        }

        return response()->json([
            'message' => 'Servicios sincronizados correctamente'
        ]);
    }




    /**
     * NO USO
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
     * NO USO
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
     * NO USO
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
     *NO USO
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
     * NO USO
     */
    private function authorizeAccess(Request $request, StaffMember $staffMember): void
    {
        $organization = $this->getOrganization($request);

        if ($staffMember->organization_id !== $organization->id) {
            abort(403, 'No autorizado.');
        }
    }


    // NO USO
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
