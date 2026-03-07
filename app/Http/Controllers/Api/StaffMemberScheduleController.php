<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StaffMemberScheduleController extends Controller
{
    use ResolvesOrganization;
    /**
     * Obtener horarios del staff
     */
    public function index(Request $request, StaffMember $staffMember): JsonResponse
    {
        $this->authorizeAccess($request, $staffMember);

        $schedules = $staffMember->schedules()
            ->orderBy('day_of_week')
            ->get();

        return response()->json($schedules);
    }

    /**
     * Actualizar horarios completos
     */
    public function update(Request $request, StaffMember $staffMember): JsonResponse
    {
        $this->authorizeAccess($request, $staffMember);

        $validated = $request->validate([
            'schedules' => ['required', 'array'],
            'schedules.*.day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.is_active' => ['boolean'],
        ]);

        DB::transaction(function () use ($staffMember, $validated) {

            // Eliminamos horarios actuales
            $staffMember->schedules()->delete();

            // Insertamos nuevos
            foreach ($validated['schedules'] as $schedule) {
                $staffMember->schedules()->create([
                    'day_of_week' => $schedule['day_of_week'],
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'is_active' => $schedule['is_active'] ?? true,
                ]);
            }
        });

        return response()->json([
            'message' => 'Horarios actualizados correctamente.'
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
