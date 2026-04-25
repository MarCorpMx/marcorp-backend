<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthorizationService
{
    public function getCurrentRole(Request $request, int $organizationId, int $subsystemId): ?string
    {
        $branchId = $request->header('X-Branch-Id');

        if (!$branchId) {
            return null; // o lanzar excepción si quieres ser estricto
        }

        return DB::table('branch_user_access as bua')
            ->join('roles as r', 'r.id', '=', 'bua.role_id')
            ->where('bua.organization_id', $organizationId)
            ->where('bua.user_id', $request->user()->id)
            ->where('bua.subsystem_id', $subsystemId)
            ->where('bua.branch_id', $branchId)
            ->where('bua.is_active', true)
            ->value('r.key');
    }

    public function authorize(Request $request, int $organizationId, int $subsystemId, array $allowedRoles): void
    {
        $role = $this->getCurrentRole($request, $organizationId, $subsystemId);

        if (!$role || !in_array($role, $allowedRoles)) {
            abort(403, 'No tienes permisos para esta acción.');
        }
    }
}
