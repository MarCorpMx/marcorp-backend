<?php

namespace App\Http\Controllers\Api\PublicBooking;

use App\Http\Controllers\Controller;

use App\Models\Organization;
use App\Models\Branch;


use App\Http\Resources\PublicBooking\PublicBookingBranchResource;
use App\Http\Resources\PublicBooking\PublicBookingBranchCardResource;
use App\Http\Resources\PublicBooking\PublicBookingOrganizationResource;

use App\Services\Organization\OrganizationPlanService;

use Illuminate\Support\Facades\Log;


class PublicBookingEntryController extends Controller
{

    protected OrganizationPlanService $planService;

    public function __construct(
        OrganizationPlanService $planService
    ) {
        $this->planService = $planService;
    }

    /*
    |--------------------------------------------------------------------------
    | bookingPublico/{organization}
    |--------------------------------------------------------------------------
    */

    public function organization(Organization $organization)
    {
        $branches = Branch::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->get();

        $plan = $this->planService
            ->getPlan(
                $organization,
                'citas'
            );


        return response()->json([
            'organization' => new PublicBookingOrganizationResource($organization),

            'plan' => [

                'key' => $plan?->key,

                'is_free' => $plan?->key === 'free',

                'is_internal' => $organization->is_internal

            ],

            'branches' => PublicBookingBranchCardResource::collection($branches)
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | bookingPublico/{organization}/{branch}
    |--------------------------------------------------------------------------
    */

    public function branch(
        Organization $organization,
        Branch $branch
    ) {

        if ($branch->organization_id !== $organization->id) {
            abort(404);
        }

        if (
            $branch->organization_id !== $organization->id ||
            !$branch->is_active
        ) {
            abort(404);
        }


        $plan = $this->planService
            ->getPlan(
                $organization,
                'citas'
            );


        return response()->json([
            'organization' => new PublicBookingOrganizationResource($organization),
            
            'branch' => new PublicBookingBranchResource($branch),
            
            'plan' => [

                'key' => $plan?->key,

                'is_free' => $plan?->key === 'free',

                'is_internal' => $organization->is_internal

            ],

        ]);
    }
}
