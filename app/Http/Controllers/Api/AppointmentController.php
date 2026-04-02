<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\AppointmentService;


class AppointmentController extends Controller
{

    use ResolvesOrganization;

    public function __construct(
        protected AppointmentService $appointmentService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Listar citas (con filtro opcional por fecha)
    |--------------------------------------------------------------------------
    */

    /* rombi - implementar lo siguiente para que puedan ver las citas correspondientes de cada usuario, solo si es admin pueda ver todas man
    $user = $request->user();

$query = Appointment::query()
    ->where('organization_id', $organization->id)
    ->with(['client','staff','serviceVariant.service']);

if ($user->role !== 'admin') {

    $staffId = $organization->staffMembers()
        ->where('user_id', $user->id)
        ->value('id');

    $query->where('staff_member_id', $staffId);
}

$appointments = $query
    ->when(
        $request->date,
        fn($q) => $q->whereDate('start_datetime', $request->date)
    )
    ->orderBy('start_datetime')
    ->get();*/
    public function index(Request $request)
    {
        $organization = $this->getOrganization($request);

        $appointments = Appointment::query()
            ->where('organization_id', $organization->id)
            ->with([
                'client',
                'staff',
                'serviceVariant.service'
            ])
            ->when(
                $request->date,
                fn($q) =>
                $q->whereDate('start_datetime', $request->date)
            )
            ->orderBy('start_datetime')
            ->get();

        return AppointmentResource::collection($appointments);
    }

    /*
    |--------------------------------------------------------------------------
    | Crear cita
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $organization = $this->getOrganization($request);

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'staff_member_id' => ['required', 'exists:staff_members,id'],
            'service_variant_id' => ['required', 'exists:service_variants,id'],
            'date' => ['required', 'date'],
            'time' => ['required'],
            'notes' => ['nullable', 'string'],
        ]);

        /*$variant = \App\Models\ServiceVariant::findOrFail($validated['service_variant_id']);

        $start = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['time']);
        $end = $start->copy()->addMinutes($variant->duration_minutes);

        $appointment = Appointment::create([
            'organization_id' => $organization->id,
            'client_id' => $validated['client_id'],
            'staff_member_id' => $validated['staff_member_id'],
            'service_variant_id' => $variant->id,

            'start_datetime' => $start,
            'end_datetime' => $end,

            'capacity_reserved' => 1,
            'status' => 'confirmed',
            'source' => 'admin_panel',

            'notes' => $validated['notes'] ?? null,
        ]);*/

        $appointment = $this->appointmentService->createAppointment(
            data: $validated,
            organization: $organization,
            options: [
                'status' => 'confirmed',
                'source' => 'admin_panel',
                'with_tokens' => false, // admin no necesita tokens
                'user_id' => $request->user()->id,
                'note' => $validated['notes'] ?? null,
                'send_notifications' => true,
                'notify_client' => true,
                'notify_internal' => false,
            ]
        );

        return new AppointmentResource(
            $appointment->load([
                'client',
                'staff',
                'serviceVariant.service'
            ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mostrar cita específica - rombi (verificar staffMember)
    |--------------------------------------------------------------------------
    */
    public function show(Request $request, Appointment $appointment)
    {
        $organization = $this->getOrganization($request);
        //$this->authorizeAppointment($request, $appointment);

        return response()->json(
            $appointment->load(['client', 'staffMember', 'service'])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar cita
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Appointment $appointment)
    {
        $organization = $this->getOrganization($request);
        //$this->authorizeAppointment($request, $appointment);

        $validated = $request->validate([
            'start_datetime' => ['sometimes', 'date'],
            'end_datetime' => ['sometimes', 'date', 'after:start_datetime'],
            'status' => [
                'sometimes',
                Rule::in([
                    'pending',
                    'confirmed',
                    'completed',
                    'rescheduled',
                    'cancelled',
                    'no_show'
                ])
            ],
            'notes' => ['nullable', 'string'],
        ]);

        $appointment->update($validated);

        return response()->json(
            $appointment->load(['client', 'staffMember', 'service'])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar estatus de la cita
    |--------------------------------------------------------------------------
    */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        $organization = $this->getOrganization($request);

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'confirmed',
                    'completed',
                    'rescheduled',
                    'cancelled',
                    'no_show'
                ])
            ],
            'note' => ['nullable', 'string', 'max:1000']
        ]);

        $userId = $request->user()->id;
        $status = $validated['status'];

        switch ($status) {

            case 'confirmed':
                $this->appointmentService->confirm(
                    $appointment,
                    [
                        'user_id' => $userId,
                        'reason' => $validated['note'] ?? 'Confirmada desde panel'
                    ],
                    'admin'
                );
                break;

            case 'cancelled':
                $this->appointmentService->cancel(
                    $appointment,
                    [
                        'user_id' => $userId,
                        'reason' => $validated['note'] ?? 'Cancelada desde panel'
                    ],
                    'admin'
                );
                break;

            case 'no_show':
                $appointment->update(['status' => 'no_show']);

                $this->appointmentService->createNote(
                    $appointment,
                    $userId,
                    'no_show',
                    $validated['note'] ?? 'Cliente no asistió'
                );
                break;

            case 'completed':
                $appointment->update(['status' => 'completed']);

                $this->appointmentService->createNote(
                    $appointment,
                    $userId,
                    'completed',
                    $validated['note'] ?? 'Cita completada'
                );
                break;

            case 'rescheduled':
                // aquí después conectas con reschedule()
                $appointment->update(['status' => 'rescheduled']);

                $this->appointmentService->createNote(
                    $appointment,
                    $userId,
                    'reschedule',
                    $validated['note'] ?? 'Cita reagendada'
                );
                break;

            default:
                $appointment->update(['status' => $status]);

                $this->appointmentService->createNote(
                    $appointment,
                    $userId,
                    'admin_note',
                    $validated['note']
                );
                break;
        }

        return response()->json([
            'data' => $appointment->fresh([
                'client',
                'serviceVariant.service'
            ])
        ]);

        //$appointment->status = $validated['status'];
        //$appointment->save();

        /*
        |-------------------------------------------------
        | Crear nota interna si hay comentario
        |-------------------------------------------------
        */
        /*if (!empty($validated['note'])) {

            //$this->appointmentService->createNote
            AppointmentNote::create([
                'appointment_id' => $appointment->id,
                'user_id' => $request->user()->id,
                'note' => $validated['note'],
                'type' => match ($validated['status']) {
                    'cancelled' => 'cancellation',
                    'no_show' => 'no_show',
                    'rescheduled' => 'reschedule',
                    default => 'admin_note'
                }
            ]);
        }*/

        /*
        |-------------------------------------------------
        | Eventos futuros (correo / notificaciones)
        |-------------------------------------------------
        */
        /*if ($appointment->status === 'confirmed') {
            // enviar correo confirmación
        }

        if ($appointment->status === 'cancelled') {
            // enviar aviso cancelación
        }*/

        /*return response()->json([
            'data' => $appointment
        ]);*/
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar cita
    |--------------------------------------------------------------------------
    */
    public function destroy(Request $request, Appointment $appointment)
    {
        //$this->authorizeAppointment($request, $appointment);

        $appointment->delete();

        return response()->json([
            'message' => 'Appointment deleted successfully'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Seguridad: evitar acceso cruzado de organizaciones
    |--------------------------------------------------------------------------
    */


    /*private function authorizeAppointment(Request $request, Appointment $appointment)
    {
        if ($appointment->organization_id !== $request->user()->organization_id) {
            abort(403, 'Unauthorized');
        }
    }*/
}
