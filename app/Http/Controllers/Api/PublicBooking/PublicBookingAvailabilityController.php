<?php

namespace App\Http\Controllers\Api\PublicBooking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use App\Services\PublicBooking\BookingAvailabilityService;

use App\Models\BranchServiceVariant;

use Illuminate\Support\Facades\Log;

class PublicBookingAvailabilityController extends Controller
{

    public function __construct(
        protected BookingAvailabilityService $availabilityService
    ) {}

    public function availability(
        BranchServiceVariant $variant,
        Request $request
    ) {


        $staffId = $request->integer('staff');

        /*return response()->json([
             'message' => 'la que va a atender es: ' . $staffId
         ], 400);*/


        $data = $this->availabilityService->getAvailability(
            $variant,
            $staffId
        );

        return response()->json($data);
    }
}
