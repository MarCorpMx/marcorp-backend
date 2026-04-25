<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PlanSubsystemFeature;
use App\Models\Subsystem;
use App\Models\Feature;
use App\Models\BranchUserAccess;
use Illuminate\Support\Facades\Cache;

class FeatureService
{
    protected int $cacheTtl = 60;

    /*
    |----------------------------------------------------------------------
    | 🔥 CORE: Obtener features usando branch_user_access
    |----------------------------------------------------------------------
    */
    public function getFeaturesFor(
        int $subsystemId,
        ?int $planId,
        int $organizationId,
        ?int $userId
    ) {

        // 🔥 SI NO HAY PLAN → NO HAY FEATURES
        if (!$planId) {
            return collect();
        }

        /*$roleKey = $this->resolveRoleFromBranches(
            $userId,
            $organizationId,
            $subsystemId
        );*/

        $roleKey = $userId
            ? $this->resolveRoleFromBranches(
                $userId,
                $organizationId,
                $subsystemId
            )
            : 'owner';

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
    | 🔥 Resolver rol REAL desde branch_user_access
    |----------------------------------------------------------------------
    */
    protected function resolveRoleFromBranches(
        int $userId,
        int $organizationId,
        int $subsystemId
    ): ?string {

        $roles = BranchUserAccess::where('user_id', $userId)
            ->where('organization_id', $organizationId)
            ->where('subsystem_id', $subsystemId)
            ->where('is_active', true)
            ->with('role')
            ->get()
            ->pluck('role.key')
            ->unique()
            ->toArray();

        if (empty($roles)) {
            return null;
        }

        $hierarchy = ['root', 'owner', 'admin', 'receptionist', 'staff'];

        foreach ($hierarchy as $role) {
            if (in_array($role, $roles)) {
                return $role;
            }
        }

        return null;
    }

    /*
    |----------------------------------------------------------------------
    | 🔥 Obtener plan_id desde organization_subsystems
    |----------------------------------------------------------------------
    */
    protected function resolvePlanId(Organization $org, int $subsystemId): ?int
    {
        $cacheKey = "org:{$org->id}:subsystem:{$subsystemId}:plan";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($org, $subsystemId) {

            $orgSubsystem = $org->subsystems()
                ->where('subsystem_id', $subsystemId)
                ->first();

            return $orgSubsystem?->pivot?->plan_id;
        });
    }

    /*
    |----------------------------------------------------------------------
    | 🔥 Obtener UNA feature
    |----------------------------------------------------------------------
    */
    public function get(
        Organization $org,
        ?int $userId,
        string $subsystemKey,
        string $featureKey
    ): ?array {

        [$subsystem, $features] = $this->resolveSubsystemAndFeatures(
            $org,
            $userId,
            $subsystemKey
        );

        if (!$subsystem) return null;

        return collect($features)->firstWhere('key', $featureKey);
    }

    /*
    |----------------------------------------------------------------------
    | 🔥 ¿Puede usar la feature?
    |----------------------------------------------------------------------
    */
    public function can(
        Organization $org,
        int $userId,
        string $key
    ): bool {

        [$subsystemKey, $featureKey] = explode('.', $key);

        $feature = $this->get($org, $userId, $subsystemKey, $featureKey);

        return $feature['enabled'] ?? false;
    }

    /*
    |----------------------------------------------------------------------
    | 🔥 Obtener límite
    |----------------------------------------------------------------------
    */
    public function limit(
        Organization $org,
        ?int $userId,
        string $key
    ): ?int {

        [$subsystemKey, $featureKey] = explode('.', $key);

        $feature = $this->get($org, $userId, $subsystemKey, $featureKey);

        return $feature['limit'] ?? null;
    }

    /*
    |----------------------------------------------------------------------
    | 🆕 Obtener feature RAW (solo plan)
    |----------------------------------------------------------------------
    */
    public function getRawFeature(
        Organization $org,
        string $subsystemKey,
        string $featureKey
    ): ?PlanSubsystemFeature {

        $subsystem = Subsystem::where('key', $subsystemKey)->first();
        if (!$subsystem) return null;

        $planId = $this->resolvePlanId($org, $subsystem->id);
        if (!$planId) return null;

        $feature = Feature::where('subsystem_id', $subsystem->id)
            ->where('key', $featureKey)
            ->first();

        if (!$feature) return null;

        return PlanSubsystemFeature::where('plan_id', $planId)
            ->where('subsystem_id', $subsystem->id)
            ->where('feature_id', $feature->id)
            ->first();
    }

    /*
    |----------------------------------------------------------------------
    | 🔥 Helper interno
    |----------------------------------------------------------------------
    */
    protected function resolveSubsystemAndFeatures(
        Organization $org,
        ?int $userId,
        string $subsystemKey
    ): array {

        $subsystem = Subsystem::where('key', $subsystemKey)->first();

        if (!$subsystem) {
            return [null, collect()];
        }

        // 🔥 AQUÍ ESTÁ EL CAMBIO CLAVE
        $planId = $this->resolvePlanId($org, $subsystem->id);

        $features = $this->getFeaturesFor(
            $subsystem->id,
            $planId,
            $org->id,
            $userId
        );

        return [$subsystem, $features];
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
    public function clearCache(): void
    {
        Cache::flush();
    }
}
