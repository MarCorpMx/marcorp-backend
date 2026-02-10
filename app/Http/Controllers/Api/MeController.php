<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\OrganizationSubsystem;
use App\Models\Plan;
use App\Models\PlanSubsystemFeature;
use App\Models\Subsystem;

class MeController extends Controller
{
    /**
     * GET /api/me
     * Info básica del usuario autenticado
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
        ]);
    }

    /**
     * GET /api/me/systems
     * Sistemas (subsistemas) que el usuario puede usar
     */
    public function systems(Request $request)
    {
        $user = $request->user();

        /**
         * Aquí asumo:
         * - User -> organizations
         * - Organization -> organizationSubsystems
         */
        $organizations = $user->organizations()
            ->with([
                'organizationSubsystems.subsystem',
                'organizationSubsystems.plan',
            ])
            ->get();

        $systems = [];

        foreach ($organizations as $organization) {
            foreach ($organization->organizationSubsystems as $orgSubsystem) {
                $systems[] = [
                    'organization_id' => $organization->id,
                    'organization_name' => $organization->name,
                    'subsystem' => [
                        'key' => $orgSubsystem->subsystem->key,
                        'name' => $orgSubsystem->subsystem->name,
                    ],
                    'plan' => $orgSubsystem->plan
                        ? [
                            'key' => $orgSubsystem->plan->key,
                            'name' => $orgSubsystem->plan->name,
                        ]
                        : null,
                    'status' => $orgSubsystem->status,
                ];
            }
        }

        return response()->json($systems);
    }

    /**
     * GET /api/me/features
     * Features disponibles según organización + subsystem
     */
    public function features(Request $request)
    {
        $request->validate([
            'organization_id' => 'required|integer',
            'subsystem' => 'required|string',
        ]);

        $orgSubsystem = OrganizationSubsystem::where('organization_id', $request->organization_id)
            ->whereHas(
                'subsystem',
                fn($q) =>
                $q->where('key', $request->subsystem)
            )
            ->with(['plan', 'subsystem'])
            ->firstOrFail();

        $features = PlanSubsystemFeature::enabled()
            ->where('plan_id', $orgSubsystem->plan_id)
            ->where('subsystem_id', $orgSubsystem->subsystem_id)
            ->with('feature')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    $row->feature->key => [
                        'enabled' => (bool) $row->is_enabled,
                        'limit' => $row->limit_value,
                    ],
                ];
            });

        return response()->json([
            'organization_id' => $orgSubsystem->organization_id,
            'subsystem' => $orgSubsystem->subsystem->key,
            'plan' => [
                'key' => $orgSubsystem->plan->key,
                'name' => $orgSubsystem->plan->name,
            ],
            'features' => $features,
        ]);
    }

    /**
     * GET /api/me/usage
     * Uso actual (placeholder / futuro)
     */
    public function usage(Request $request)
    {
        /**
         * Esto normalmente vendrá de:
         * - usage_logs
         * - counters por feature
         * - métricas agregadas
         *
         * Por ahora dejamos una estructura estable
         */
        return response()->json([
            'message' => 'Usage endpoint ready',
            'data' => [],
        ]);
    }

    /**
     * GET /api/me/subscription
     * Plan actual del sistema activo 
     */
    public function subscription(Request $request)
    {
        $request->validate([
            'organization_id' => 'required|integer',
            'subsystem' => 'required|string',
        ]);

        $orgSubsystem = OrganizationSubsystem::where('organization_id', $request->organization_id)
            ->whereHas(
                'subsystem',
                fn($q) =>
                $q->where('key', $request->subsystem)
            )
            ->with('plan')
            ->firstOrFail();

        return response()->json([
            'plan' => [
                'key' => $orgSubsystem->plan->key,
                'name' => $orgSubsystem->plan->name,
                'price' => $orgSubsystem->plan->price,
                'started_at' => $orgSubsystem->started_at,
                'is_paid' => (bool) $orgSubsystem->is_paid,
            ]
        ]);
    }

    /**
     * GET /api/me/plans
     * Todos los planes disponibles para un subsistema, con features + límites
     */
    public function plans(Request $request)
    {
        $request->validate([
            'subsystem' => 'required|string',
        ]);

        $subsystem = Subsystem::where('key', $request->subsystem)->firstOrFail();

        $plans = Plan::with(['features' => function ($q) use ($subsystem) {
            $q->where('subsystem_id', $subsystem->id)
                ->where('is_enabled', true)
                ->with('feature');
        }])->get();

        return response()->json(
            $plans->map(function ($plan) {
                return [
                    'key' => $plan->key,
                    'name' => $plan->name,
                    'price' => $plan->price,
                    'description' => $plan->description,
                    'features' => $plan->features->map(function ($f) {
                        return [
                            'key' => $f->feature->key,
                            'name' => $f->feature->name,
                            'enabled' => (bool) $f->is_enabled,
                            'limit' => $f->limit_value,
                        ];
                    })->values()
                ];
            })
        );
    }
}
