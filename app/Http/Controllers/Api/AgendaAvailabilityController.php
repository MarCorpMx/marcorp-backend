<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Professional;
use App\Services\AgendaAvailabilityService;
use Illuminate\Http\Request;

class AgendaAvailabilityController extends Controller
{
    public function index(Request $request, $professionalId)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $professional = Professional::findOrFail($professionalId);

        $service = new AgendaAvailabilityService();

        $slots = $service->getAvailableSlots(
            $professional,
            $request->date
        );

        return response()->json([
            'date' => $request->date,
            'professional' => $professional->name,
            'slots' => $slots,
        ]);
    }
}