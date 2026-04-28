<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

use App\Models\Subsystem;


class ResolveSubsystem
{
    public function handle(Request $request, Closure $next): Response
    {
        $subsystemId = $request->header('X-Subsystem-Id');

        if (!$subsystemId) {
            return response()->json([
                'message' => 'Subsystem no especificado'
            ], 400);
        }

        $subsystem = Subsystem::find($subsystemId);

        if (!$subsystem) {
            return response()->json([
                'message' => 'Subsystem inválido'
            ], 404);
        }

        // usar organization del contexto (NO del user directo)
        $organization = $request->attributes->get('organization');

        if (!$organization) {
            return response()->json([
                'message' => 'Organización no resuelta'
            ], 500);
        }

        $hasAccess = DB::table('organization_subsystems')
            ->where('organization_id', $organization->id)
            ->where('subsystem_id', $subsystem->id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Subsystem no disponible para esta organización'
            ], 403);
        }

        $request->attributes->set('subsystem', $subsystem);

        app()->instance('currentSubsystem', $subsystem);

        return $next($request);
    }
}
