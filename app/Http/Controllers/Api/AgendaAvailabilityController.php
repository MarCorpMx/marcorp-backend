<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use App\Services\AgendaAvailabilityService;
use Illuminate\Http\Request;

class AgendaAvailabilityController extends Controller
{
    public function index(Request $request, $staffMemberId)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $staffMember = StaffMember::findOrFail($staffMemberId);

        $service = new AgendaAvailabilityService();

        $slots = $service->getAvailableSlots(
            $staffMember,
            $request->date
        );

        return response()->json([
            'date' => $request->date,
            'staffMember' => $staffMember->name,
            'slots' => $slots,
        ]);
    }
}