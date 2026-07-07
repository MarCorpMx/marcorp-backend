<?php

namespace App\Services\Organization;

use App\Models\Organization;
use App\Models\OrganizationSubsystem;

class OrganizationPlanService
{
    /*
    |--------------------------------------------------------------------------
    | Obtener subscripción de subsystem
    |--------------------------------------------------------------------------
    */

    public function getSubsystemSubscription(
        Organization $organization,
        string $subsystemKey
    ): ?OrganizationSubsystem {

        /*if ($organization->is_internal) {
            return true;
        }*/

        return OrganizationSubsystem::query()

            ->where('organization_id', $organization->id)

            ->whereHas('subsystem', function ($q) use ($subsystemKey) {
                $q->where('key', $subsystemKey);
            })

            ->with([
                'subsystem',
                'plan'
            ])

            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Obtener plan
    |--------------------------------------------------------------------------
    */

    public function getPlan(
        Organization $organization,
        string $subsystemKey
    ) {
        if ($organization->is_internal) {

            return (object)[
                'key' => 'internal',
                'is_internal' => true
            ];
        }

        $subscription = $this->getSubsystemSubscription(
            $organization,
            $subsystemKey
        );

        return $subscription?->plan;
    }

    /*
    |--------------------------------------------------------------------------
    | Saber si tiene plan free
    |--------------------------------------------------------------------------
    */

    public function isFreePlan(
        Organization $organization,
        string $subsystemKey
    ): bool {

        $plan = $this->getPlan(
            $organization,
            $subsystemKey
        );

        return $plan?->key === 'free';
    }

    /*
    |--------------------------------------------------------------------------
    | Saber si es premium (SE TIENE QUE MEJORAR PARA VERIFICAR TODOS LOS PLANES)
    |--------------------------------------------------------------------------
    */

    public function isPremium(
        Organization $organization,
        string $subsystemKey
    ): bool {

        $plan = $this->getPlan(
            $organization,
            $subsystemKey
        );

        if (!$plan) {
            return false;
        }

        return !in_array($plan->key, [
            'free'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Estatus de subscripción
    |--------------------------------------------------------------------------
    */
    public function isActiveSubscription(
        Organization $organization,
        string $subsystemKey
    ): bool {

        $subscription = $this->getSubsystemSubscription(
            $organization,
            $subsystemKey
        );

        if (!$subscription) {
            return false;
        }

        return in_array(
            $subscription->status,
            ['active', 'trial']
        );
    }
}
