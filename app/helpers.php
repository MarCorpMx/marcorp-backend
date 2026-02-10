<?php

use App\Models\OrganizationSubsystem;
use App\Models\PlanSubsystemFeature;

if (! function_exists('canUseFeature')) {

    function canUseFeature(
        OrganizationSubsystem $orgSubsystem,
        string $featureKey,
        ?int $currentUsage = null
    ): bool {

        $feature = PlanSubsystemFeature::enabled()
            ->where('plan_id', $orgSubsystem->plan_id)
            ->where('subsystem_id', $orgSubsystem->subsystem_id)
            ->whereHas('feature', fn ($q) =>
                $q->where('key', $featureKey)
            )
            ->first();

        if (! $feature) {
            return false;
        }

        // Sin límite → permitido
        if ($feature->limit_value === null) {
            return true;
        }

        // Con límite pero sin uso actual → permitido
        if ($currentUsage === null) {
            return true;
        }

        return $currentUsage < $feature->limit_value;
    }
}

if (! function_exists('canSeeFeature')) {

    function canSeeFeature(
        OrganizationSubsystem $orgSubsystem,
        string $featureKey
    ): bool {
        return canUseFeature($orgSubsystem, $featureKey);
    }
}

if (! function_exists('featureLimit')) {

    function featureLimit(
        OrganizationSubsystem $orgSubsystem,
        string $featureKey
    ): ?int {

        return PlanSubsystemFeature::where([
            'plan_id' => $orgSubsystem->plan_id,
            'subsystem_id' => $orgSubsystem->subsystem_id,
        ])
        ->whereHas('feature', fn ($q) =>
            $q->where('key', $featureKey)
        )
        ->value('limit_value');
    }
}

