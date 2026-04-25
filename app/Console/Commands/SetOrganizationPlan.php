<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subsystem;
use App\Services\PlanEnforcementService;
use App\Services\FeatureService;

class SetOrganizationPlan extends Command
{
    protected $signature = 'org:set-plan {orgId} {planKey}';
    protected $description = 'Cambiar plan de una organización';

    protected $enforcer;
    protected $featureService;

    public function __construct(
        PlanEnforcementService $enforcer,
        FeatureService $featureService
    ) {
        parent::__construct();
        $this->enforcer = $enforcer;
        $this->featureService = $featureService;
    }

    public function handle()
    {
        $orgId = $this->argument('orgId');
        $planKey = $this->argument('planKey');

        $org = Organization::find($orgId);

        if (!$org) {
            $this->error('Organización no encontrada');
            return;
        }

        // 🔥 Obtener subsystem (citas)
        $subsystem = Subsystem::where('key', 'citas')->first();

        if (!$subsystem) {
            $this->error('Subsystem citas no existe');
            return;
        }

        // 🔥 Obtener plan
        $plan = Plan::where('key', $planKey)
            ->where('subsystem_id', $subsystem->id)
            ->first();

        if (!$plan) {
            $this->error('Plan no encontrado');
            return;
        }

        // 🔥 Actualizar organization_subsystems (pivot)
        $org->subsystems()
            ->updateExistingPivot($subsystem->id, [
                'plan_id' => $plan->id,
                'status' => 'active',
                'is_paid' => true,
                'started_at' => now(),
                'cancelled_at' => null,
            ]);

        $this->info("Plan actualizado a {$plan->name}");

        // 🔥 Limpiar cache
        $this->featureService->clearCache();

        // 🔥 ENFORCEMENT (lo importante)
        $this->enforcer->enforce($org);

        $this->info('Enforcement aplicado correctamente');

        return 0;
    }
}
