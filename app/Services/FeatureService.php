<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PlanSubsystemFeature;
use App\Models\Subsystem;
use App\Models\Feature;
use Illuminate\Support\Facades\Cache;

class FeatureService
{
    protected int $cacheTtl = 60;

    /*
    |----------------------------------------------------------------------
    | 🔥 CORE: Obtener features por subsystem
    |----------------------------------------------------------------------
    */
    public function getFeaturesFor(
        int $subsystemId,
        ?int $planId,
        ?string $roleKey,
        ?int $organizationId = null
    ) {

        $cacheKey = "features:org:{$organizationId}:subsystem:{$subsystemId}:plan:{$planId}:role:{$roleKey}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($subsystemId, $planId, $roleKey) {

            $features = Feature::where('subsystem_id', $subsystemId)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('id');

            $planFeatures = PlanSubsystemFeature::where('subsystem_id', $subsystemId)
                ->where('plan_id', $planId)
                ->get()
                ->keyBy('feature_id');

            return $features->map(function ($feature) use ($planFeatures, $roleKey) {

                $planFeature = $planFeatures->get($feature->id);

                $isEnabledByPlan = $planFeature?->is_enabled ?? false;
                $isVisible = $planFeature?->is_visible ?? false;
                $limit = $planFeature?->limit_value ?? null;

                $roleAllows = $this->checkRoleAccess($feature->key, $roleKey);

                return [
                    'key' => $feature->key,
                    'label' => $feature->menu_label ?? $feature->name,
                    'description' => $feature->description,
                    'route' => $feature->menu_route,
                    'icon' => $feature->menu_icon,

                    'enabled' => $roleAllows && $isEnabledByPlan,
                    'visible' => $isVisible,

                    'limit' => $limit,

                    'parent' => $feature->parent_key,
                    'sort_order' => $feature->sort_order,
                    'is_core' => $feature->is_core,
                ];
            })->values();
        });
    }

    /*
    |----------------------------------------------------------------------
    | 🔥 Obtener UNA feature (clave tipo: citas.branches)
    |----------------------------------------------------------------------
    */
    public function get(Organization $org, string $subsystemKey, string $featureKey): ?array
    {
        $subsystem = Subsystem::where('key', $subsystemKey)->first();

        if (!$subsystem) return null;

        $features = $this->getFeaturesFor(
            $subsystem->id,
            $org->plan_id,
            $org->role_key ?? 'owner',
            $org->id
        );

        return collect($features)->firstWhere('key', $featureKey);
    }

    /*
    |----------------------------------------------------------------------
    | 🔥 ¿Puede usar la feature?
    |----------------------------------------------------------------------
    */
    public function can(Organization $org, string $key): bool
    {
        [$subsystemKey, $featureKey] = explode('.', $key);

        $feature = $this->get($org, $subsystemKey, $featureKey);

        return $feature['enabled'] ?? false;
    }

    /*
    |----------------------------------------------------------------------
    | 🔥 Obtener límite
    |----------------------------------------------------------------------
    */
    public function limit(Organization $org, string $key): ?int
    {
        [$subsystemKey, $featureKey] = explode('.', $key);

        $feature = $this->get($org, $subsystemKey, $featureKey);

        return $feature['limit'] ?? null;
    }

    /*
    |----------------------------------------------------------------------
    | 🔥 Roles
    |----------------------------------------------------------------------
    */
    protected function checkRoleAccess(string $featureKey, ?string $roleKey): bool
    {
        if (!$roleKey) return false;

        $map = [
            'root' => ['*'],
            'owner' => ['*'],
            'admin' => ['*'],

            'receptionist' => [
                'dashboard',
                'agenda',
                'clients',
                'services',
                'schedule',
                'reminders',
                'reports',
                'team',
                'settings',
                'profile',
                'schedule_config'
            ],

            'staff' => [
                'dashboard',
                'agenda',
                'clients',
            ],
        ];

        $allowed = $map[$roleKey] ?? [];

        return in_array('*', $allowed) || in_array($featureKey, $allowed);
    }

    /*
    |----------------------------------------------------------------------
    | 🔥 Limpiar cache
    |----------------------------------------------------------------------
    */
    public function clearCache(Organization $org): void
    {
        Cache::flush(); // 🔥 simplificado (puedes hacerlo más fino luego)
    }
}
