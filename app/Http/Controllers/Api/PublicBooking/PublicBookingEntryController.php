<?php

namespace App\Http\Controllers\Api\PublicBooking;

use App\Http\Controllers\Controller;

use App\Models\Organization;
use App\Models\Branch;

use App\Models\Subsystem;
use App\Models\Plan;

use App\Http\Resources\PublicBooking\PublicBookingBranchResource;
use App\Http\Resources\PublicBooking\PublicBookingOrganizationResource;



class PublicBookingEntryController extends Controller
{
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

        // 🔥 Obtener subsystem (citas)
        $subsystem = Subsystem::where('key', 'citas')->first();

        if (!$subsystem) {
            //$this->error('Subsystem citas no existe');
            return;
        }

        // 🔥 Obtener plan
        $planKey = 2; // hardcodeado, no sirve
        $plan = Plan::where('key', $planKey)
            ->where('subsystem_id', $subsystem->id)
            ->first();

        if (!$plan) {
            //$this->error('Plan no encontrado');
            return;
        }

        return response()->json([
            'organization' => new PublicBookingOrganizationResource($organization),

            'plan' => 'perfecciongray',

            'branches' => PublicBookingBranchResource::collection($branches)
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

        return response()->json([
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],

            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
                'city' => $branch->city,
            ],

            'services' => []
        ]);
    }
}
