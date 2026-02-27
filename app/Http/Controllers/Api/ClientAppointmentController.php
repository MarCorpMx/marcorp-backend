<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Models\Client;
use App\Models\Appointment;
use Illuminate\Http\Request;

class ClientAppointmentController extends Controller
{
    use ResolvesOrganization;

    /*
    |--------------------------------------------------------------------------
    | INDEX - Historial de citas del cliente
    |--------------------------------------------------------------------------
    */
    public function index(Request $request, Client $client)
    {
        $organization = $this->getOrganization($request);

        // 🔐 Multi-tenant safety
        abort_if(
            $client->organization_id !== $organization->id,
            403,
            'Client does not belong to your organization.'
        );

        $appointments = Appointment::query()
            ->where('organization_id', $organization->id)
            ->where('client_id', $client->id)
            ->with([
                'serviceVariant.service',
                'professional'
            ])
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->from, function ($q) use ($request) {
                $q->whereDate('start_time', '>=', $request->from);
            })
            ->when($request->to, function ($q) use ($request) {
                $q->whereDate('start_time', '<=', $request->to);
            })
            ->orderByDesc('start_time')
            ->paginate(15);

        return $appointments->through(function ($appointment) {

            return [
                'id' => $appointment->id,
                'start_time' => $appointment->start_time,
                'end_time' => $appointment->end_time,
                'status' => $appointment->status,
                'service' => [
                    'id' => $appointment->serviceVariant?->service?->id,
                    'name' => $appointment->serviceVariant?->service?->name,
                ],
                'variant' => [
                    'id' => $appointment->serviceVariant?->id,
                    'name' => $appointment->serviceVariant?->name,
                    'duration_minutes' => $appointment->serviceVariant?->duration_minutes,
                ],
                'professional' => [
                    'id' => $appointment->professional?->id,
                    'name' => $appointment->professional?->full_name,
                ],
                'capacity_reserved' => $appointment->capacity_reserved,
                'created_at' => $appointment->created_at,
            ];
        });
    }
}