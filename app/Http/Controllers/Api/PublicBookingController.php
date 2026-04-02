<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\ServiceVariant;
use App\Models\Appointment;
use App\Services\NotificationService;
use App\Models\AppointmentActionToken;
use App\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;



class PublicBookingController extends Controller
{

    protected AppointmentService $appointmentService;
    public function __construct(
        NotificationService $notificationService,
        AppointmentService $appointmentService
    ) {
        $this->appointmentService = $appointmentService;
    }

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
                        'price',
                        'mode'
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

        // Staff que pueden realizar esta variante
        $staffMembers = $variant->staff()->with([
            'agendaSetting',
            'schedules',
            'nonWorkingDays'
        ])->get();

        $slots = [];

        foreach ($staffMembers as $staff) {

            // Verificar día no laboral
            $isBlocked = $staff->nonWorkingDays()
                ->whereDate('date', $date)
                ->exists();

            if ($isBlocked) {
                continue;
            }

            $dayOfWeek = $date->dayOfWeek;

            $schedule = $staff->schedules
                ->where('day_of_week', $dayOfWeek)
                ->first();

            if (!$schedule) {
                continue;
            }

            $agenda = $staff->agendaSetting;

            if (!$agenda) {
                continue;
            }

            $duration = $variant->duration_minutes;
            $break = $agenda->break_between_appointments ?? 0;

            $start = Carbon::parse($schedule->start_time)
                ->setDateFrom($date);

            $end = Carbon::parse($schedule->end_time)
                ->setDateFrom($date);

            $appointments = Appointment::where('staff_member_id', $staff->id)
                ->whereDate('start_datetime', $date)
                ->whereIn('status', ['pending', 'confirmed'])
                ->get();

            while ($start->copy()->addMinutes($duration)->lte($end)) {

                $slotEnd = $start->copy()->addMinutes($duration);

                $conflict = $appointments->first(function ($appointment) use ($start, $slotEnd) {

                    $appointmentStart = Carbon::parse($appointment->start_datetime);
                    $appointmentEnd = Carbon::parse($appointment->end_datetime);

                    return $start < $appointmentEnd &&
                        $slotEnd > $appointmentStart;
                });

                if (!$conflict) {

                    $slots[] = [
                        'time' => $start->format('H:i'),
                        'staff_member_id' => $staff->id
                    ];
                }

                //$start->addMinutes($duration + $break);
                $start->addMinutes(15);
            }
        }

        // FILTRO DE HORAS PASADAS
        $now = now()->addMinutes(30);

        $slots = collect($slots)
            ->filter(function ($slot) use ($date, $now) {

                $slotDateTime = Carbon::parse(
                    $date->toDateString() . ' ' . $slot['time']
                );

                // Si la fecha es hoy, no permitir horarios pasados
                if ($date->isToday()) {
                    return $slotDateTime->greaterThan($now);
                }

                return true;
            })
            ->values()
            ->toArray();

        // Verificar si no hay slots disponibles
        if (empty($slots)) {
            return response()->json([
                'date' => $date->toDateString(),
                'available_slots' => [],
                'available' => false
            ]);
        }

        // ordenar y eliminar duplicados
        $slots = collect($slots)
            ->sortBy('time')
            ->unique('time')
            ->values();

        return response()->json([
            'date' => $date->toDateString(),
            'available_slots' => $slots,
            'available' => true
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /availabilityRange
    |--------------------------------------------------------------------------
    */
    public function availabilityRange(Request $request, Organization $organization): JsonResponse
    {
        $request->validate([
            'service_variant_id' => ['required', 'exists:service_variants,id'],
            'start_date' => ['nullable', 'date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:30']
        ]);

        $variant = ServiceVariant::whereHas('service', function ($query) use ($organization) {
            $query->where('organization_id', $organization->id);
        })
            ->where('id', $request->service_variant_id)
            ->where('active', true)
            ->firstOrFail();

        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)
            : now()->startOfDay();

        $days = (int) ($request->days ?? 9);

        $cacheKey = "availability_range_{$organization->id}_{$variant->id}_{$startDate->toDateString()}_{$days}";

        $results = Cache::remember($cacheKey, 60, function () use ($variant, $organization, $startDate, $days) {

            $endDate = $startDate->copy()->addDays($days);

            $staffMembers = $variant->staff()->with([
                'agendaSetting',
                'schedules',
                'nonWorkingDays'
            ])->get();

            /*$appointments = Appointment::whereIn(
                'staff_member_id',
                $staffMembers->pluck('id')
            )
                ->whereBetween('start_datetime', [$startDate, $endDate])
                ->get()
                ->groupBy('staff_member_id');*/

            $appointments = Appointment::whereIn(
                'staff_member_id',
                $staffMembers->pluck('id')
            )
                ->whereBetween('start_datetime', [$startDate, $endDate])
                ->whereIn('status', ['pending', 'confirmed'])
                ->get()
                ->groupBy('staff_member_id');

            $results = [];

            for ($i = 0; $i < $days; $i++) {

                $date = $startDate->copy()->addDays($i);
                $dayOfWeek = $date->dayOfWeek;

                $slotsCount = 0;

                foreach ($staffMembers as $staff) {

                    $blocked = $staff->nonWorkingDays
                        ->where('date', $date->toDateString())
                        ->isNotEmpty();

                    if ($blocked) {
                        continue;
                    }

                    $schedule = $staff->schedules
                        ->where('day_of_week', $dayOfWeek)
                        ->first();

                    if (!$schedule) {
                        continue;
                    }

                    $agenda = $staff->agendaSetting;

                    if (!$agenda) {
                        continue;
                    }

                    $duration = $variant->duration_minutes;
                    $break = $agenda->break_between_appointments ?? 0;

                    $start = Carbon::parse($schedule->start_time)->setDateFrom($date);
                    $end = Carbon::parse($schedule->end_time)->setDateFrom($date);

                    $staffAppointments = $appointments[$staff->id] ?? collect();

                    while ($start->copy()->addMinutes($duration)->lte($end)) {

                        $slotEnd = $start->copy()->addMinutes($duration);

                        $conflict = $staffAppointments->first(function ($appointment) use ($start, $slotEnd) {

                            $appointmentStart = Carbon::parse($appointment->start_datetime);
                            $appointmentEnd = Carbon::parse($appointment->end_datetime);

                            return $start < $appointmentEnd &&
                                $slotEnd > $appointmentStart;
                        });

                        if (!$conflict) {
                            $slotsCount++;
                        }

                        //$start->addMinutes($duration + $break);
                        $start->addMinutes(15);
                    }
                }

                $results[] = [
                    'date' => $date->toDateString(),
                    'available' => $slotsCount > 0,
                    'slots' => $slotsCount
                ];
            }

            return $results;
        });

        return response()->json([
            'days' => $results
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /appointments
    |--------------------------------------------------------------------------
    */
    public function store(Request $request, Organization $organization): JsonResponse
    {
        // Honeypot anti bot
        if ($request->filled('website')) {
            abort(403, 'Spam detected.');
        }

        // delay humano
        /*$formTime = (int) $request->input('form_time', 0);
        if (app()->environment('production')) {
            if (!$formTime || now()->timestamp - $formTime < 3) {
                abort(429, 'Too fast.');
            }
        }*/

        $validated = $request->validate([
            'service_variant_id' => ['required', 'exists:service_variants,id'],
            'staff_member_id' => ['required', 'exists:staff_members,id'],
            'date' => ['required', 'date'],
            'time' => ['required'],

            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email'],

            'phone' => ['nullable', 'array'],
            'phone.number' => ['nullable', 'string'],
            'phone.e164Number' => ['nullable', 'string'],
            'phone.countryCode' => ['nullable', 'string', 'size:2'],
            'phone.dialCode' => ['nullable', 'string'],

            'notes' => ['nullable', 'string', 'max:500'],

            'mode' => ['required', 'string', 'max:100'],

        ]);

        // Evitar spam de reservas
        // rombi - descomentar para seguridad
        $recentBooking = Appointment::where('organization_id', $organization->id)
            ->whereHas('client', function ($q) use ($validated) {
                $q->where('email', $validated['email']);
            })
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($recentBooking) {
            return response()->json([
                'message' => 'Espera unos minutos antes de solicitar otra cita.'
            ], 429);
        }

        // rombi - descomentar
        /*$key = 'booking_attempts_' . $request->ip();
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addMinutes(10));
        if ($attempts > 20) {
            abort(429, 'Too many attempts.');
        }*/

        /*
        |--------------------------------------------------------------------------
        | Verificar variante
        |--------------------------------------------------------------------------
        */
        $variant = ServiceVariant::whereHas('service', function ($query) use ($organization) {
            $query->where('organization_id', $organization->id);
        })
            ->where('id', $validated['service_variant_id'])
            ->where('active', true)
            ->firstOrFail();

        // Valida el staff disponible
        $validStaff = $variant->staff()
            ->where('staff_members.id', $validated['staff_member_id'])
            ->exists();

        if (!$validStaff) {
            return response()->json([
                'message' => 'Staff inválido para este servicio'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Construir fechas
        |--------------------------------------------------------------------------
        */
        $start = Carbon::parse($validated['date'] . ' ' . $validated['time']);
        $end = $start->copy()->addMinutes($variant->duration_minutes);

        /*
        |--------------------------------------------------------------------------
        | No permitir fechas pasadas
        |--------------------------------------------------------------------------
        */
        if ($start->lessThan(now())) {
            return response()->json([
                'message' => 'No se puede agendar en horarios pasados'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Verificar conflictos - rombi (evita que 2 personas reserven al mismo tiempo)
        |--------------------------------------------------------------------------
        */
        /*$conflict = Appointment::where('staff_member_id', $validated['staff_member_id'])
            ->whereIn('status', ['pending', 'confirmed']) // se agrego para interferir
            ->where(function ($query) use ($start, $end) {

                $query->whereBetween('start_datetime', [$start, $end])
                    ->orWhereBetween('end_datetime', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_datetime', '<', $start)
                            ->where('end_datetime', '>', $end);
                    });
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'Este horario ya fue reservado x'
            ], 409);
        }*/

        /*
        |--------------------------------------------------------------------------
        | Crear cita
        |--------------------------------------------------------------------------
        */

        $appointment = $this->appointmentService->createAppointment(
            data: $validated,
            organization: $organization,
            options: [
                'status' => 'pending',
                'source' => 'public_web',
                'with_tokens' => true,
                'user_id' => null,
                'note' => 'Cita creada por cliente',
                'send_notifications' => true,
                'notify_client' => true,
                'notify_internal' => true,
            ]
        );

        return response()->json([
            'message' => 'Appointment created',
            'data' => $appointment->load([
                'client',
                'staff',
                'serviceVariant.service'
            ]),
            /*'debug_urls' => [
                'confirm' => $confirmUrl,
                'cancel' => $cancelUrl,
            ]*/
        ], 201);
    }
}
