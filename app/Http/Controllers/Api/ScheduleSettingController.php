<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrganizationScheduleSetting;

class ScheduleSettingController extends Controller
{
    public function getSchedule(Request $request)
    {
        $org = $request->user()->organizations()->first(); // por simplicidad, la primera organización
        if (!$org) {
            return response()->json(['message' => 'No organization found'], 404);
        }

        $schedule = OrganizationScheduleSetting::firstOrCreate(
            ['organization_id' => $org->id],
            [
                'break_between_appointments' => 10,
                'working_hours' => [],
                'holidays' => [],
                'rules' => ''
            ]
        );

        return response()->json($schedule);
    }

    public function updateSchedule(Request $request)
    {
        $org = $request->user()->organizations()->first();
        if (!$org) {
            return response()->json(['message' => 'No organization found'], 404);
        }

        $schedule = OrganizationScheduleSetting::updateOrCreate(
            ['organization_id' => $org->id],
            $request->only([
                'break_between_appointments',
                'working_hours',
                'holidays',
                'rules'
            ])
        );

        return response()->json($schedule);
    }
}