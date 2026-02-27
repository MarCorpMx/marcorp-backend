<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Professional;
use App\Models\AgendaSetting;
use App\Models\ProfessionalSchedule;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    use ResolvesOrganization;

    public function show(Request $request, Professional $professional)
    {
        $this->authorizeProfessional($request, $professional);

        $agenda = $professional->agendaSettings ?? [
            'appointment_duration' => 60,
            'break_between_appointments' => 0,
            'minimum_notice_hours' => 12,
            'cancellation_limit_hours' => 24,
            'allow_online_booking' => true,
            'allow_cancellation' => true,
        ];
        $schedules = $professional->schedules;

        return response()->json([
            'data' => [
                'settings' => $agenda,
                'weekly_schedule' => $schedules
            ]
        ]);
    }

    public function update(Request $request, Professional $professional)
    {
        $this->authorizeProfessional($request, $professional);

        /*$validated = $request->validate([
            'appointment_duration' => 'required|integer|min:5',
            'break_between_slots' => 'nullable|integer|min:0',
            'weekly_schedule' => 'required|array'
        ]);*/

        $validated = $request->validate([
            'appointment_duration' => 'required|integer|min:5',
            'break_between_appointments' => 'nullable|integer|min:0',
            'minimum_notice_hours' => 'nullable|integer|min:0',
            'cancellation_limit_hours' => 'nullable|integer|min:0',
            'allow_online_booking' => 'required|boolean',
            'allow_cancellation' => 'required|boolean',
            'weekly_schedule' => 'required|array'
        ]);

        // Update agenda settings
        $professional->agendaSettings()->updateOrCreate(
            ['professional_id' => $professional->id],
            [
                'appointment_duration' => $validated['appointment_duration'],
                'break_between_appointments' => $validated['break_between_appointments'] ?? 0,
                'minimum_notice_hours' => $validated['minimum_notice_hours'] ?? 0,
                'cancellation_limit_hours' => $validated['cancellation_limit_hours'] ?? 0,
                'allow_online_booking' => $validated['allow_online_booking'],
                'allow_cancellation' => $validated['allow_cancellation'],
            ]
        );

        // Reset weekly schedule
        ProfessionalSchedule::where(
            'professional_id',
            $professional->id
        )->delete();

        foreach ($validated['weekly_schedule'] as $day) {
            ProfessionalSchedule::create([
                'professional_id' => $professional->id,
                'day_of_week' => $day['day_of_week'],
                'start_time' => $day['start_time'],
                'end_time' => $day['end_time']
            ]);
        }

        return response()->json([
            'message' => 'Agenda updated successfully'
        ]);
    }

    private function authorizeProfessional(Request $request, Professional $professional)
    {
        $organization = $this->getOrganization($request);

        abort_if(
            $professional->organization_id !== $organization->id,
            403
        );
    }
}
