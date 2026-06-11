<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Http\Resources\AppointmentResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Models\Appointment;
use App\Models\BranchUserAccess;
use App\Models\BranchStaff;
use App\Models\BranchServiceVariant;
use App\Models\BranchServiceVariantStaff;
use App\Models\StaffMember;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Requests\StoreAppointmentRequest;

use App\Services\AppointmentService;
use App\Services\SubsystemResolver;
use App\Services\FeatureService;
use App\Services\AppointmentValidationService;
use App\Services\AppointmentLimitService;


class AppointmentController extends Controller
{

    use ResolvesOrganization;

    public function __construct(
        protected AppointmentService $appointmentService,
        protected SubsystemResolver $subsystemResolver,
        protected FeatureService $featureService,
        protected AppointmentValidationService $validationService,
        protected AppointmentLimitService $appointmentLimitService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Listar citas (con filtro opcional por fecha)
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        /*
        |----------------------------------------------------------------------
        | CONTEXTO SaaS
        |----------------------------------------------------------------------
        */
        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');
        $user = $request->user();

        /*
        |----------------------------------------------------------------------
        | SUBSYSTEM
        |----------------------------------------------------------------------
        */
        $subsystemCode = 'citas';

        $subsystemId = $this->subsystemResolver->resolve($subsystemCode);

        if (!$subsystemId) {
            abort(500, "Subsystem '{$subsystemCode}' no encontrado.");
        }

        /*
        |----------------------------------------------------------------------
        | ACCESSO DEL USUARIO EN ESTA SUCURSAL
        |----------------------------------------------------------------------
        */
        $access = BranchUserAccess::query()
            ->active()
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->where('user_id', $user->id)
            ->where('subsystem_id', $subsystemId)
            ->with('role')
            ->first();

        if (!$access) {
            abort(403, 'No tienes acceso a este módulo.');
        }

        $role = $access->role?->key;

        /*
        |----------------------------------------------------------------------
        | QUERY BASE
        |----------------------------------------------------------------------
        */
        $query = Appointment::query()
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->with([
                'client',
                'staff',
                'serviceVariant.service',
                'appointmentNotes.user'
            ]);

        /*
        |----------------------------------------------------------------------
        | FILTRO POR ROL
        |----------------------------------------------------------------------
        */
        if ($role === 'staff') {

            /*
            |--------------------------------------------------------------
            | STAFF SOLO VE SUS CITAS
            |--------------------------------------------------------------
            */
            if (!$access->staff_member_id) {
                abort(403, 'Tu usuario no tiene staff asignado.');
            }

            $query->where(
                'staff_member_id',
                $access->staff_member_id
            );
        } else {

            /*
            |--------------------------------------------------------------
            | OWNER / ADMIN / RECEPTIONIST
            |--------------------------------------------------------------
            */
            if ($request->filled('staff_member_id')) {

                $staffExists = StaffMember::query()
                    ->where('organization_id', $organization->id)
                    ->where('id', $request->staff_member_id)
                    ->whereHas('branches', function ($q) use ($branch) {
                        $q->where('branches.id', $branch->id);
                    })
                    ->exists();

                if (!$staffExists) {
                    abort(403, 'Profesional no válido para esta sucursal.');
                }

                $query->where(
                    'staff_member_id',
                    $request->staff_member_id
                );
            }
        }

        /*
        |----------------------------------------------------------------------
        | FILTRO POR RANGO (FULLCALENDAR)
        |----------------------------------------------------------------------
        */
        if ($request->filled('start') && $request->filled('end')) {

            $query->where(function ($q) use ($request) {

                $q->whereBetween('start_datetime', [
                    $request->start,
                    $request->end
                ])
                    ->orWhereBetween('end_datetime', [
                        $request->start,
                        $request->end
                    ]);
            });
        }

        /*
        |----------------------------------------------------------------------
        | FILTRO POR DÍA
        |----------------------------------------------------------------------
        */ elseif ($request->filled('date')) {

            $query->whereDate(
                'start_datetime',
                $request->date
            );
        }

        /*
        |----------------------------------------------------------------------
        | ORDEN
        |----------------------------------------------------------------------
        */
        $appointments = $query
            ->orderBy('start_datetime')
            ->get();

        /*
        |----------------------------------------------------------------------
        | RESPONSE
        |----------------------------------------------------------------------
        */
        return AppointmentResource::collection($appointments);
    }



    /*
    |--------------------------------------------------------------------------
    | Crear cita
    |--------------------------------------------------------------------------
    */
    public function store(StoreAppointmentRequest $request)
    {

        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');
        $subsystem = $request->attributes->get('subsystem');

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Validamos permisos de acceso
        |--------------------------------------------------------------------------
        */
        if (!$this->featureService->can($organization, $user->id, 'citas.agenda')) {
            abort(403, 'No tienes acceso a esta funcionalidad');
        }

        /*
        |------------------------------------------------------------------
        | Validar límite de citas (mensualmente)
        |------------------------------------------------------------------
        */
        $limit = $this->featureService->limit(
            $organization,
            $user->id,
            'citas.agenda'
        );

        $this->appointmentLimitService
            ->validateLimitCanCreate(
                $organization,
                $limit
            );


        // Validaciones de payload
        $validated = $request->validated();

        // Validaciones cliente - staff - variant - canProvideVariant
        $resultCreate = $this->validationService
            ->validateAdminCreate(
                $organization,
                $branch,
                $validated
            );


        $appointment = $this->appointmentService->createAppointment(
            data: $validated,
            organization: $organization,
            client: $resultCreate['client'],
            pet: $resultCreate['pet'],
            staff: $resultCreate['staff'],
            variant: $resultCreate['variant'],
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
