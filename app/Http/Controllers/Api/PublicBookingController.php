<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PublicBookingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET /services
    |--------------------------------------------------------------------------
    */
    public function services(Organization $organization): JsonResponse
    {
        $services = $organization->services()
            ->where('is_active', true)
            ->select('id', 'name', 'description', 'duration', 'price')
            ->get();

        return response()->json([
            'data' => $services
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /availability
    |--------------------------------------------------------------------------
    */
    public function availability(Request $request, Organization $organization): JsonResponse
    {
        $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'date' => ['required', 'date']
        ]);

        $service = Service::where('organization_id', $organization->id)
            ->where('id', $request->service_id)
            ->where('is_active', true)
            ->firstOrFail();

        $date = Carbon::parse($request->date);

        // ⚠️ Aquí luego llamaremos a AvailabilityService
        // Por ahora regresamos mock vacío

        return response()->json([
            'date' => $date->toDateString(),
            'available_slots' => []
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /appointments
    |--------------------------------------------------------------------------
    */
    public function store(Request $request, Organization $organization): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email'],
            'client_phone' => ['nullable', 'string'],
            'start_time' => ['required', 'date']
        ]);

        $service = Service::where('organization_id', $organization->id)
            ->where('id', $validated['service_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $start = Carbon::parse($validated['start_time']);
        $end = $start->copy()->addMinutes($service->duration);

        // ⚠️ Aquí luego validaremos disponibilidad real

        $appointment = Appointment::create([
            'organization_id' => $organization->id,
            'service_id' => $service->id,
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'],
            'client_phone' => $validated['client_phone'] ?? null,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'confirmed'
        ]);

        return response()->json([
            'message' => 'Appointment created successfully',
            'data' => $appointment
        ], 201);
    }
}