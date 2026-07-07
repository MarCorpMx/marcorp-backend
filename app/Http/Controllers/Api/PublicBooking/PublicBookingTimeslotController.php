<?php

namespace App\Http\Controllers\Api\PublicBooking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Services\PublicBooking\BookingAvailabilityService;


use App\Models\BranchServiceVariant;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PublicBookingTimeslotController extends Controller
{

    public function __construct(
        protected BookingAvailabilityService $availabilityService
    ) {}


    public function timeslots(
        Request $request,
        BranchServiceVariant $variant
    ) {

        $request->validate([
            'date' => ['required', 'date'],
            'staff' => ['nullable', 'integer']
        ]);

        return response()->json([
            'data' => $this->availabilityService->getTimeSlots(
                $variant,
                Carbon::parse($request->date),
                $request->staff
            )
        ]);
    }

    
}
