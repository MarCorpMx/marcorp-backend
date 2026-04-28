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

        // 1. Si no viene branch → error
        if (!$branchId) {
            return response()->json([
                'message' => 'Branch no especificado'
            ], 400);
        }

        // 2. Buscar branch
        $branch = Branch::find($branchId);

        if (!$branch) {
            return response()->json([
                'message' => 'Branch inválido'
            ], 404);
        }

        // 3. Validar que pertenece a la organización del usuario
        $user = $request->user();

        $hasAccess = $user->organizations()
            ->where('organization_id', $branch->organization_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a esta sucursal'
            ], 403);
        }

        // 4. Inyectar branch al request
        $request->attributes->set('branch', $branch);

        return $next($request);
    }
}
