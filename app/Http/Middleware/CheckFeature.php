<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PlanSubsystemFeature;

class CheckFeature
{
    /**
     * feature:agenda
     * feature:agenda,agenda
     */
    public function handle(Request $request, Closure $next, string $featureKey)
    {
        /** @var \App\Models\OrganizationSubsystem|null $orgSubsystem */
        $orgSubsystem = $request->get('orgSubsystem');

        if (! $orgSubsystem) {
            return response()->json([
                'message' => 'Organization subsystem not resolved'
            ], 400);
        }

        $feature = PlanSubsystemFeature::enabled()
            ->where('plan_id', $orgSubsystem->plan_id)
            ->where('subsystem_id', $orgSubsystem->subsystem_id)
            ->whereHas('feature', fn ($q) =>
                $q->where('key', $featureKey)
            )
            ->first();

        if (! $feature) {
            return response()->json([
                'message' => 'Feature not available for your plan'
            ], 403);
        }

        // Guardamos el feature en el request (útil en el controller)
        $request->attributes->set('feature', $feature);

        return $next($request);
    }
}
