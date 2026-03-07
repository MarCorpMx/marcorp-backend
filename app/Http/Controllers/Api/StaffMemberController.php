<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Concerns\ResolvesOrganization;

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
     * Crear nuevo staff member
     */
    public function store(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;

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
