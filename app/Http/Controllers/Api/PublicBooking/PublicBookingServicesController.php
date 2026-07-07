<?php

namespace App\Http\Controllers\Api\PublicBooking;

use App\Http\Controllers\Controller;

use App\Models\Organization;
use App\Models\Branch;
use App\Models\BranchService;

use App\Http\Resources\PublicBooking\PublicBookingBranchServiceResource;


use Illuminate\Support\Facades\Log;


class PublicBookingServicesController extends Controller
{
    public function services(
        Organization $organization,
        Branch $branch
    ) {

        $services = BranchService::query()
            ->forOrganization($organization->id)
            ->forBranch($branch->id)
            ->active()

            ->with([

                'variants' => function ($query) {

                    $query
                        ->active()
                        ->orderBy('sort_order')

                        ->with([

                            'staffMembers' => function ($staff) {

                                $staff
                                    ->wherePivot('active', true)
                                    ->where('is_active', true)
                                    ->where('is_public', true)
                                    ->select(
                                        'staff_members.id',
                                        'name',
                                        'title',
                                        'specialty',
                                        'bio',
                                        'avatar'
                                    );
                            }

                        ]);
                }

            ])

            ->orderBy('sort_order')
            ->get();

        return PublicBookingBranchServiceResource::collection($services);
    }
}
