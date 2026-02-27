<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NonWorkingDay;
use App\Models\Professional;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use Illuminate\Http\Request;

class NonWorkingDayController extends Controller
{
    use ResolvesOrganization;
    
    public function index(Request $request, Professional $professional)
    {
        $this->authorizeProfessional($request, $professional);

        return response()->json([
            'data' => $professional->nonWorkingDays
        ]);
    }

    public function store(Request $request, Professional $professional)
    {
        $this->authorizeProfessional($request, $professional);

        $validated = $request->validate([
            'date' => 'required|date',
            'reason' => 'nullable|string|max:255'
        ]);

        $validated['professional_id'] = $professional->id;

        $day = NonWorkingDay::create($validated);

        return response()->json([
            'message' => 'Day added',
            'data' => $day
        ], 201);
    }

    public function destroy(Request $request, Professional $professional, NonWorkingDay $day)
    {
        $this->authorizeProfessional($request, $professional);

        abort_if(
            $day->professional_id !== $professional->id,
            403
        );

        $day->delete();

        return response()->json([
            'message' => 'Day removed'
        ]);
    }

    private function authorizeProfessional($request, $professional)
    {
        $organization = $this->getOrganization($request);

        abort_if(
            $professional->organization_id !== $organization->id,
            403
        );
    }
}
