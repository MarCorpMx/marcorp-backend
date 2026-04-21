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

    /**
     * GET /api/me/organization
     * Obtener los datos de la organización
     */
    public function organization(Request $request)
    {
        $organization = $request->user()->currentOrganization();

        return response()->json($organization);
    }

    /**
     * PUT /api/me/organization
     * Actualizar los datos de la organización
     */
    public function updateOrganization(Request $request)
    {
        $organization = $request->user()->currentOrganization();

        /*
    |----------------------------------------------------------
    | DETECTAR MODO
    |----------------------------------------------------------
    */
        $isOnboarding = !$organization->onboarding_completed_at
            && $organization->onboarding_step === Organization::ONBOARDING_BUSINESS_SETUP;

        /*
    |----------------------------------------------------------
    | VALIDACIÓN DINÁMICA
    |----------------------------------------------------------
    */
        if ($isOnboarding) {

            $data = $request->validate([
                'name' => ['required', 'string', 'min:3', 'max:120'],
                'phone' => ['required', 'array'],
                'country' => ['required', 'string', 'size:2'],
                'state' => ['nullable', 'string', 'max:100'],
                'city' => ['nullable', 'string', 'max:100'],
            ]);
        } else {

            $data = $request->validate([
                'name' => ['required', 'string', 'min:3', 'max:120'],
                'email' => ['nullable', 'email', 'max:255'],

                'phone' => ['nullable', 'array'],

                'website' => ['nullable', 'url', 'max:255'],

                'primary_color' => ['nullable', 'string', 'max:20'],
                'secondary_color' => ['nullable', 'string', 'max:20'],
                'logo_url' => ['nullable', 'url'],

                'timezone' => ['required', 'string'],

                // PRO / PREMIUM stuff (puedes bloquear con FeatureService luego)
                'primary_domain' => ['nullable', 'string', 'max:255'],
                'domains' => ['nullable', 'array'],
            ]);
        }

        /*
    |----------------------------------------------------------
    | UPDATE ORGANIZATION
    |----------------------------------------------------------
    */
        $organization->update($data);

        /*
    |----------------------------------------------------------
    | 🔥 SYNC CON PRIMARY BRANCH (SOLO ONBOARDING)
    |----------------------------------------------------------
    */
        if ($isOnboarding) {

            $primaryBranch = $organization->branches()
                ->where('is_primary', true)
                ->first();

            if ($primaryBranch) {
                $primaryBranch->update([
                    'name' => $data['name'],
                    'phone' => $data['phone'] ?? null,
                    'country' => $data['country'],
                    'state' => $data['state'] ?? null,
                    'city' => $data['city'] ?? null,
                ]);
            }
        }

        /*
    |----------------------------------------------------------
    | ONBOARDING FLOW
    |----------------------------------------------------------
    */
        if ($isOnboarding) {

            if (
                $organization->name &&
                $organization->phone &&
                $organization->country
            ) {
                $organization->advanceOnboarding(
                    Organization::ONBOARDING_SERVICE_CREATED
                );
            }

            return response()->json([
                'message' => 'Negocio configurado correctamente',
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'onboarding_step' => $organization->onboarding_step,
                    'onboarding_completed_at' => $organization->onboarding_completed_at,
                ]
            ]);
        }

        /*
    |----------------------------------------------------------
    | FLOW NORMAL
    |----------------------------------------------------------
    */
        return response()->json([
            'message' => 'Organización actualizada correctamente',
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ]
        ]);
    }

    public function updateOrganization_BKP(Request $request)
    {
        $organization = $request->user()->currentOrganization();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'country' => ['required', 'string', 'size:2'],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],

            'phone' => ['required', 'array'],

            /*'phone.e164Number' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],
            'phone.internationalNumber' => ['nullable', 'string', 'max:30'],
            'phone.nationalNumber' => ['nullable', 'string', 'max:20'],
            'phone.countryCode' => ['required', 'string', 'size:2'],
            'phone.dialCode' => ['required', 'string', 'max:5'],*/
        ]);

        $organization->update($data);

        /*$organization->update($request->only([
            'name',
            'email',
            'phone',
            'website',
            'country',
            'state',
            'city',
            'zip_code',
            'address',
            'primary_color',
            'secondary_color',
        ]));*/

        // ONBOARDING LOGIC
        if (!$organization->onboarding_completed_at) {

            switch ($organization->onboarding_step) {

                case Organization::ONBOARDING_BUSINESS_SETUP:

                    // validación mínima (puedes endurecer luego)
                    if ($organization->name && $organization->phone && $organization->country) {

                        $organization->advanceOnboarding(
                            Organization::ONBOARDING_SERVICE_CREATED
                        );
                    }

                    break;
            }
        }

        return response()->json([
            'message' => 'Organización actualizada correctamente',
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'onboarding_step' => $organization->onboarding_step,
                'onboarding_completed_at' => $organization->onboarding_completed_at,
            ]
        ]);
    }
}
