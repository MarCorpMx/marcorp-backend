<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Branch;

use App\Services\FeatureService;

class PlanEnforcementService
{
    protected $featureService;

    public function __construct(FeatureService $featureService)
    {
        $this->featureService = $featureService;
    }

    public function enforce(Organization $org): void
    {
        $limit = $this->featureService->limit($org, null, 'citas.branches');

        // Ilimitado → no hacer nada
        //if (is_null($limit)) return;
        if (is_null($limit)) {

            // Plan ilimitado → desbloquear TODO
            Branch::where('organization_id', $org->id)
                ->update([
                    'locked_by_plan' => false,
                    'is_active' => true // opcional, pero recomendado
                ]);

            return;
        }

        $branches = Branch::where('organization_id', $org->id)
            ->orderByDesc('is_primary') // primero la principal
            ->orderByDesc('is_active')  // luego activas
            ->orderBy('id')             // orden estable
            ->get();

        $activeCount = 0;

        foreach ($branches as $branch) {
            if ($activeCount < $limit) {

                if (!$branch->is_active) {
                    $branch->update([
                        'is_active' => true,
                        'locked_by_plan' => false
                    ]);
                }

                $activeCount++;
            } else {

                if ($branch->is_active) {
                    $branch->update([
                        'is_active' => false,
                        'locked_by_plan' => true
                    ]);
                }
            }
        }
    }
}
