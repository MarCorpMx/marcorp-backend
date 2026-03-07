<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\ServiceVariant;
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
    public function services(\App\Models\Organization $organization)
    {
        $services = $organization->services()
            ->where('active', true)
            ->with(['variants' => function ($q) {
                $q->where('active', true)
                    ->select(
                        'id',
                        'service_id',
                        'name',
                        'duration_minutes',
                        'price'
                    );
            }])
            ->select(
                'id',
                'organization_id',
                'name',
                'description'
            )
            ->orderBy('name')
            ->get();

        return response()->json($services);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /availability
    |--------------------------------------------------------------------------
    */
    public function availability(Request $request, Organization $organization): JsonResponse
    {
        $request->validate([
            'service_variant_id' => ['required', 'exists:service_variants,id'],
            'date' => ['required', 'date']
        ]);

        $variant = ServiceVariant::whereHas('service', function ($query) use ($organization) {
            $query->where('organization_id', $organization->id);
        })
            ->where('id', $request->service_variant_id)
            ->where('active', true)
            ->firstOrFail();

        $date = Carbon::parse($request->date);

        // Aquí después llamaremos a AvailabilityService real
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
            'service_variant_id' => ['required', 'exists:service_variants,id'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email'],
            'client_phone' => ['nullable', 'string'],
            'start_time' => ['required', 'date'],
        ]);

        $variant = ServiceVariant::whereHas('service', function ($query) use ($organization) {
            $query->where('organization_id', $organization->id);
        })
            ->where('id', $validated['service_variant_id'])
            ->where('active', true)
            ->firstOrFail();

        $start = Carbon::parse($validated['start_time']);
        $end = $start->copy()->addMinutes($variant->duration_minutes);

        $appointment = Appointment::create([
            'organization_id' => $organization->id,
            'service_id' => $variant->service_id,
            'service_variant_id' => $variant->id,
            'staff_member_id' => null, // Se puede asignar automáticamente después
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
