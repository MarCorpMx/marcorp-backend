<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use App\Models\ServiceVariant;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class StaffMemberController extends Controller
{
    use ResolvesOrganization;

    /**
     * Listar staff members de la organización autenticada
     */
    public function index(Request $request): JsonResponse
    {
        $organization = $this->getOrganization($request);

        $staffMembers = $organization->staffMembers()
            ->with(['agendaSetting'])
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
        //$organization = $request->user()->organization;
        $organization = $this->getOrganization($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'active' => ['boolean'],
        ]);

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
}
