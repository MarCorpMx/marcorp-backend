<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use App\Models\StaffMemberNonWorkingDay;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Concerns\ResolvesOrganization;

use Illuminate\Support\Facades\Log;


class StaffMemberNonWorkingDayController extends Controller
{

    use ResolvesOrganization;
    /**
     * Listar días no laborables
     */
    public function index(Request $request, StaffMember $staffMember): JsonResponse
    {
        $this->authorizeAccess($request, $staffMember);

        $branch = $request->attributes->get('branch');

        /*
        |--------------------------------------------------------------------------
        | Solo días no laborables de ESTA sucursal
        |--------------------------------------------------------------------------
        */

        $days = $staffMember->nonWorkingDays()
            ->where('branch_id', $branch->id)
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'data' => $days
        ]);
    }

    /**
     * Crear día no laborable
     */
    public function store(Request $request, StaffMember $staffMember): JsonResponse
    {
        $this->authorizeAccess($request, $staffMember);


        Log::info('dianita creadora va lola');


        $validated = $request->validate([
            'date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        // Evitar duplicados
        $exists = $staffMember->nonWorkingDays()
            ->where('date', $validated['date'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ese día ya está marcado como no laborable.'
            ], 422);
        }

        $day = $staffMember->nonWorkingDays()->create($validated);

        return response()->json($day, 201);
    }

    /**
     * Eliminar día no laborable
     */
    public function destroy(
        Request $request,
        StaffMember $staffMember,
        StaffMemberNonWorkingDay $day
    ): JsonResponse {
        $this->authorizeAccess($request, $staffMember);

        Log::info('dianita anda de destructora');

        // Asegurar que el día pertenece al staff correcto
        if ($day->staff_member_id !== $staffMember->id) {
            abort(404);
        }

        $day->delete();

        return response()->json([
            'message' => 'Día no laborable eliminado correctamente.'
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
