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
        $branch = $request->attributes->get('branch');
        $subsystem = $request->attributes->get('subsystem');

        $user = $request->user();

        $subsystemId = $subsystem->id;
        $branchId = $branch->id;

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
        | QUERY REAL (LO QUE NECESITAS)
        |------------------------------------------------------------------
        */

        $query = $organization->staffMembers()
            ->where('is_active', true)
            ->whereExists(function ($q) use ($branchId) {
                $q->select(DB::raw(1))
                    ->from('branch_staff as bs')
                    ->whereColumn('bs.staff_member_id', 'staff_members.id')
                    ->where('bs.branch_id', $branchId);
            });

        /*
        |------------------------------------------------------------------
        | SOLO STAFF VE SOLO SU REGISTRO
        |------------------------------------------------------------------
        */

        if ($role === 'staff') {
            $query->where('user_id', $user->id);
        }

        /*
        |------------------------------------------------------------------
        | FILTROS OPCIONALES
        |------------------------------------------------------------------
        */

        if ($request->has('public')) {
            $query->where('is_public', (bool) $request->public);
        }

        /*
        |------------------------------------------------------------------
        | RESULT
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
    | Solo servicios asignados en ESTA sucursal
    |--------------------------------------------------------------------------
    */

        $ids = $staffMember->serviceVariants()
            ->wherePivot('branch_id', $branch->id)
            ->pluck('service_variants.id');

        return response()->json([
            'data' => $ids
        ]);
    }

    /**
     * Se usa para actualizar los servicios del staff
     */
    public function syncServiceVariants(Request $request, StaffMember $staffMember)
    {
        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');

        if ($staffMember->organization_id !== $organization->id) {
            abort(403);
        }

        $validated = $request->validate([
            'service_variant_ids' => ['required', 'array'],
            'service_variant_ids.*' => ['exists:service_variants,id']
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validar que los servicios pertenezcan a la organización
        |--------------------------------------------------------------------------
        */

        $validIds = ServiceVariant::whereIn('id', $validated['service_variant_ids'])
            ->whereHas('service', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })
            ->pluck('id')
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Borrar SOLO de esta sucursal
        |--------------------------------------------------------------------------
        */

        DB::table('service_variant_staff')
            ->where('staff_member_id', $staffMember->id)
            ->where('branch_id', $branch->id)
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | Insertar nuevos
        |--------------------------------------------------------------------------
        */

        $rows = collect($validIds)->map(function ($id) use ($staffMember, $branch) {
            return [
                'staff_member_id' => $staffMember->id,
                'service_variant_id' => $id,
                'branch_id' => $branch->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        if (!empty($rows)) {
            DB::table('service_variant_staff')->insert($rows);
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
