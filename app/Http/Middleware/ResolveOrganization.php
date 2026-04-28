<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganization
{
    public function handle(Request $request, Closure $next)
    {
        $organizationId = $request->header('X-Organization-Id');

        if (!$organizationId) {
            return response()->json([
                'message' => 'Organización no especificada'
            ], 400);
        }

        $organization = \App\Models\Organization::find($organizationId);

        if (!$organization) {
            return response()->json([
                'message' => 'Organización inválida'
            ], 404);
        }

        $request->attributes->set('organization', $organization);

        return $next($request);
    }
}
