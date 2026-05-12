<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Branch;

class ResolveBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        $branchId = $request->header('X-Branch-Id');

        /*
        |--------------------------------------------------------------------------
        | 1. Header requerido
        |--------------------------------------------------------------------------
        */
        if (!$branchId) {
            return response()->json([
                'message' => 'Branch no especificado'
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Buscar sucursal
        |--------------------------------------------------------------------------
        */
        $branch = Branch::find($branchId);

        if (!$branch) {
            return response()->json([
                'message' => 'Branch inválido'
            ], 404);
        }

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | 3. Validar membresía básica a la organización
        | (owner/root antiguos, onboarding, etc.)
        |--------------------------------------------------------------------------
        */
        $belongsToOrganization = $user->organizations()
            ->where('organization_id', $branch->organization_id)
            ->exists();

        /*
        |--------------------------------------------------------------------------
        | 4. Validar acceso real por branch_user_access
        |--------------------------------------------------------------------------
        */
        $hasBranchAccess = \App\Models\BranchUserAccess::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $branch->organization_id)
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->exists();

        /*
        |--------------------------------------------------------------------------
        | 5. Permitir si cumple alguno:
        | - pertenece a la organización
        | - tiene acceso explícito a sucursal
        |--------------------------------------------------------------------------
        */
        if (!$belongsToOrganization && !$hasBranchAccess) {
            return response()->json([
                'message' => 'No tienes acceso a esta sucursal'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Seguridad extra: branch debe pertenecer a org actual resuelta
        |--------------------------------------------------------------------------
        */
        $organization = $request->attributes->get('organization');

        if ($organization && $branch->organization_id !== $organization->id) {
            return response()->json([
                'message' => 'La sucursal no pertenece a la organización actual'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Inyectar branch resuelta
        |--------------------------------------------------------------------------
        */
        $request->attributes->set('branch', $branch);

        return $next($request);
    }
}
