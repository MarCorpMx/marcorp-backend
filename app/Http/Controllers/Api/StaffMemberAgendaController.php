<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Models\StaffMemberSchedule;

class StaffMemberAgendaController extends Controller
{
    use ResolvesOrganization;

    /**
     * Mostrar configuración de agenda
     */
    public function show(Request $request, StaffMember $staffMember): JsonResponse
    {
        $this->authorizeAccess($request, $staffMember);

        $agenda = $staffMember->agendaSetting;

        // Crear configuración por defecto si no existe
        if (!$agenda) {
            $agenda = $staffMember->agendaSetting()->create([
                'appointment_duration' => 60,
                'break_between_appointments' => 0,
                'allow_online_booking' => true,
                'minimum_notice_hours' => 2,
                'allow_cancellation' => true,
                'cancellation_limit_hours' => 12,
                'timezone' => 'America/Mexico_City',
            ]);
        }

        return response()->json([
            'data' => [
                'settings' => $agenda,
                'weekly_schedule' => $staffMember->schedules ?? []
            ]
        ]);
    }

    /**
     * Actualizar configuración de agenda
     */
    public function update(Request $request, StaffMember $staffMember): JsonResponse
    {
        $this->authorizeAccess($request, $staffMember);

        $validated = $request->validate([
            'appointment_duration' => ['required', 'integer', 'min:5', 'max:480'],
            'break_between_appointments' => ['nullable', 'integer', 'min:0', 'max:240'],
            'allow_online_booking' => ['required', 'boolean'],
            'minimum_notice_hours' => ['nullable', 'integer', 'min:0', 'max:168'],
            'allow_cancellation' => ['required', 'boolean'],
            'cancellation_limit_hours' => ['nullable', 'integer', 'min:0', 'max:168'],
            'timezone' => ['nullable', 'string'],
            'weekly_schedule' => ['required', 'array'],
            'non_working_days' => ['nullable', 'array'],
            'non_working_days.*.date' => ['required', 'date'],
            'non_working_days.*.reason' => ['nullable', 'string'],
        ]);

        // Update agenda settings
        $staffMember->agendaSetting()->updateOrCreate(
            ['staff_member_id' => $staffMember->id],
            [
                'appointment_duration' => $validated['appointment_duration'],
                'break_between_appointments' => $validated['break_between_appointments'] ?? 0,
                'minimum_notice_hours' => $validated['minimum_notice_hours'] ?? 0,
                'cancellation_limit_hours' => $validated['cancellation_limit_hours'] ?? 0,
                'allow_online_booking' => $validated['allow_online_booking'],
                'allow_cancellation' => $validated['allow_cancellation'],
                'timezone' => $validated['timezone'] ?? 'America/Mexico_City',
            ]
        );


        // Reset weekly schedule
        StaffMemberSchedule::where(
            'staff_member_id',
            $staffMember->id
        )->delete();

        // Recreate schedule
        foreach ($validated['weekly_schedule'] as $day) {
            StaffMemberSchedule::create([
                'staff_member_id' => $staffMember->id,
                'day_of_week' => $day['day_of_week'],
                'start_time' => $day['start_time'],
                'end_time' => $day['end_time'],
            ]);
        }

        // Reset non working days
        $staffMember->nonWorkingDays()->delete();

        if (!empty($validated['non_working_days'])) {
            foreach ($validated['non_working_days'] as $day) {
                $staffMember->nonWorkingDays()->create([
                    'date' => $day['date'],
                    'reason' => $day['reason'] ?? null,
                ]);
            }
        }

        return response()->json([
            'message' => 'Agenda updated successfully',
            'data' => [
                'settings' => $staffMember->agendaSetting,
                'weekly_schedule' => $staffMember->schedules,
                'non_working_days' => $staffMember->nonWorkingDays ?? []
            ]
        ]);
    }

    /**
     * Seguridad multi-tenant
     */
    private function authorizeAccess(Request $request, StaffMember $staffMember): void
    {
        $organization = $this->getOrganization($request);

        if ($staffMember->organization_id !== $organization->id) {
            abort(403, 'No autorizado.');
        }
    }
}
