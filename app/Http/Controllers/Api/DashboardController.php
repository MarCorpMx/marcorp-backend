<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Services\FeatureService;
use App\Services\BranchProfileCompletionService;
use App\Services\DashboardService;

use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    use ResolvesOrganization;

    public function __construct(
        protected FeatureService $featureService,
        protected BranchProfileCompletionService $profileCompletionService,
        protected DashboardService $dashboardService,
    ) {}

    public function DataDashboard(Request $request)
    {
        $org = $this->getOrganization($request);
        $user = $request->user();
        $branch = $request->attributes->get('branch');

        /*
        |----------------------------------------------------------
        | Validamos permisos de acceso
        |----------------------------------------------------------
        */
        if (!$this->featureService->can($org, $user->id, 'citas.dashboard')) {
            abort(403, 'No tienes acceso a este módulo');
        }

        $profileCompletion = $this->profileCompletionService
            ->calculate($branch);

        /*return response()->json([
            'message' => 'mensaje de prueba ' . $branch->id
        ], 400);*/

        return response()->json([
            'data' => [
                'branch' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                ],

                'profile_completion' => $profileCompletion,

                ...$this->dashboardService ->getData($branch),
            ]
        ]);
    }
}
