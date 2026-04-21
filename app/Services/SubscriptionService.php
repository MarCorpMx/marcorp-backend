<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\PlanSubsystemFeature;


class SubscriptionService
{
    function getSubscriptionDates($plan)
    {
        $now = now();

        switch ($plan->billing_period) {
            case 'monthly':
                $end = $now->copy()->addMonth();
                break;

            case 'yearly':
                $end = $now->copy()->addYear();
                break;

            case 'lifetime':
                $end = null;
                break;

            default:
                $end = $now->copy()->addMonth();
        }

        return [
            'started_at' => $now,
            'renews_at'  => $end,
            'expires_at' => $end,
        ];
    }
}
