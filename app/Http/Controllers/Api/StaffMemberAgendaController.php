<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Models\StaffMember;
use App\Models\BlockedSlot;
use App\Models\StaffMemberSchedule;
use App\Models\Organization;

use App\Services\AppointmentAvailabilityService;
use App\Helpers\AvailabilityErrorHelper;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;




class StaffMemberAgendaController extends Controller
{
    use ResolvesOrganization;
    protected $availabilityService;

    public function __construct(AppointmentAvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

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

        $staffMember->load([
            'schedules',
            'recurringBlocks',
            'blockedSlots',
            'nonWorkingDays'
        ]);

        return response()->json([
            'data' => [
                'settings' => $agenda,
                'weekly_schedule' => $staffMember->schedules ?? [],
                'recurring_blocks' => $staffMember->recurringBlocks ?? [],
                'non_working_days' => $staffMember->nonWorkingDays ?? [],
                'blocked_slots' => $staffMember->blockedSlots ?? []
            ]
        ]);
    }

    /**
     * Actualizar configuración de agenda
     */

    protected function getBranchId(Request $request, Organization $organization): int
    {
        $user = $request->user();
        $branchId = (int) $request->header('X-Branch-Id');

        if (!$branchId) {
            abort(400, 'Sucursal no especificada');
        }

        $hasAccess = DB::table('branch_user_access')
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'No tienes acceso a esta sucursal');
        }

        return $branchId;
    }


    public function update(Request $request, StaffMember $staffMember): JsonResponse
    {
        $user = $request->user();
        $organization = $staffMember->organization;

        /*
        |----------------------------------------------------------
        | DETECTAR ONBOARDING
        |----------------------------------------------------------
        */
        $isOnboarding = !$organization->onboarding_completed_at
            && $organization->onboarding_step === Organization::ONBOARDING_AVAILABILITY_SET;

        /*
        |----------------------------------------------------------
        | GUARD ANTI-BYPASS
        |----------------------------------------------------------
        */
        if (!$organization->onboarding_completed_at) {
            if ($organization->onboarding_step !== Organization::ONBOARDING_AVAILABILITY_SET) {
                return response()->json([
                    'message' => 'No puedes configurar horarios en este momento del onboarding'
                ], 403);
            }
        }

        /*
        |----------------------------------------------------------
        | RESOLVER STAFF Y BRANCH
        |----------------------------------------------------------
        */
        if ($isOnboarding) {
            $staffMember = $organization->staffMembers()
                ->where('user_id', $user->id)
                ->firstOrFail();

            $branchId = $organization->branches()
                ->where('is_primary', true)
                ->value('id');

        } else {
            $this->authorizeAccess($request, $staffMember);

            $branchId = $this->getBranchId($request, $organization);

        }

        /*
        |----------------------------------------------------------
        | VALIDACIÓN
        |----------------------------------------------------------
        */
        if ($isOnboarding) {
            $validated = $request->validate([
                'appointment_duration' => ['required', 'integer', 'min:5', 'max:480'],
                'break_between_appointments' => ['nullable', 'integer', 'min:0', 'max:240'],
                'allow_online_booking' => ['required', 'boolean'],
                'minimum_notice_hours' => ['nullable', 'integer', 'min:0', 'max:168'],
                'allow_cancellation' => ['required', 'boolean'],
                'cancellation_limit_hours' => ['nullable', 'integer', 'min:0', 'max:168'],
                'timezone' => ['nullable', 'string'],

                'weekly_schedule' => ['required', 'array', 'min:1'],
                'weekly_schedule.*.day_of_week' => ['required', 'integer', 'between:0,6'],
                'weekly_schedule.*.start_time' => ['required'],
                'weekly_schedule.*.end_time' => ['required'],
            ]);
        } else {
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

                'recurring_blocks' => ['nullable', 'array'],
                'recurring_blocks.*.day_of_week' => ['required', 'integer', 'between:0,6'],
                'recurring_blocks.*.start' => ['required'],
                'recurring_blocks.*.end' => ['required'],
                'recurring_blocks.*.label' => ['nullable', 'string'],
            ]);
        }

        /*
        |----------------------------------------------------------
        | VALIDACIONES BASE
        |----------------------------------------------------------
        */
        foreach ($validated['weekly_schedule'] as $day) {
            if ($day['start_time'] >= $day['end_time']) {
                return response()->json([
                    'message' => 'Horario inválido en agenda'
                ], 422);
            }
        }

        /*
        |----------------------------------------------------------
        | VALIDACIONES AVANZADAS (SOLO NORMAL)
        |----------------------------------------------------------
        */
        if (!$isOnboarding) {
            $blocksByDay = collect($validated['recurring_blocks'] ?? [])
                ->groupBy('day_of_week');

            $scheduleByDay = collect($validated['weekly_schedule'])
                ->keyBy('day_of_week');

            foreach ($blocksByDay as $day => $blocks) {

                if (!isset($scheduleByDay[$day])) {
                    return response()->json([
                        'message' => 'Bloque en día no laboral'
                    ], 422);
                }

                $workDay = $scheduleByDay[$day];
                $sorted = collect($blocks)->sortBy('start')->values();

                foreach ($sorted as $index => $block) {

                    if ($block['start'] >= $block['end']) {
                        return response()->json([
                            'message' => 'Bloque con horario inválido'
                        ], 422);
                    }

                    if (
                        $block['start'] < $workDay['start_time'] ||
                        $block['end'] > $workDay['end_time']
                    ) {
                        return response()->json([
                            'message' => 'Bloque fuera de horario laboral'
                        ], 422);
                    }

                    if ($index < count($sorted) - 1) {
                        $next = $sorted[$index + 1];

                        if ($block['end'] > $next['start']) {
                            return response()->json([
                                'message' => 'Bloques encimados'
                            ], 422);
                        }
                    }
                }
            }
        }

        /*
        |----------------------------------------------------------
        | GUARDAR
        |----------------------------------------------------------
        */


        // SETTINGS (clave: ahora por staff + branch)
        $staffMember->agendaSettings()->updateOrCreate(
            [
                'staff_member_id' => $staffMember->id,
                'branch_id' => $branchId,
            ],
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

        // SCHEDULE (delete por branch)
        $staffMember->schedules()
            ->where('branch_id', $branchId)
            ->delete();

        foreach ($validated['weekly_schedule'] as $day) {
            $staffMember->schedules()->create([
                'branch_id' => $branchId,
                'day_of_week' => $day['day_of_week'],
                'start_time' => $day['start_time'],
                'end_time' => $day['end_time'],
            ]);
        }

        /*
        |----------------------------------------------------------
        | SOLO NORMAL
        |----------------------------------------------------------
        */
        if (!$isOnboarding) {

            $staffMember->nonWorkingDays()
                ->where('branch_id', $branchId)
                ->delete();

            foreach ($validated['non_working_days'] ?? [] as $day) {
                $staffMember->nonWorkingDays()->create([
                    'branch_id' => $branchId,
                    'date' => $day['date'],
                    'reason' => $day['reason'] ?? null,
                ]);
            }

            $staffMember->recurringBlocks()
                ->where('branch_id', $branchId)
                ->delete();

            foreach ($validated['recurring_blocks'] ?? [] as $block) {
                $staffMember->recurringBlocks()->create([
                    'branch_id' => $branchId,
                    'day_of_week' => $block['day_of_week'],
                    'start_time' => $block['start'],
                    'end_time' => $block['end'],
                    'label' => $block['label'] ?? null,
                ]);
            }
        }

        /*
        |----------------------------------------------------------
        | ONBOARDING NEXT STEP
        |----------------------------------------------------------
        */
        if ($isOnboarding) {
            $organization->advanceOnboarding(
                Organization::ONBOARDING_COMPLETED
            );

            return response()->json([
                'message' => 'Horario configurado correctamente',
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'onboarding_step' => $organization->onboarding_step,
                    'onboarding_completed_at' => $organization->onboarding_completed_at,
                ]
            ]);
        }

        /*
        |----------------------------------------------------------
        | RESPONSE NORMAL (FILTRADO POR BRANCH)
        |----------------------------------------------------------
        */
        $staffMember->load([
            'agendaSetting' => fn($q) => $q->where('branch_id', $branchId),
            'schedules' => fn($q) => $q->where('branch_id', $branchId),
            'nonWorkingDays' => fn($q) => $q->where('branch_id', $branchId),
            'recurringBlocks' => fn($q) => $q->where('branch_id', $branchId),
        ]);

        return response()->json([
            'message' => 'Agenda updated successfully',
            'data' => [
                'settings' => $staffMember->agendaSetting,
                'weekly_schedule' => $staffMember->schedules,
                'non_working_days' => $staffMember->nonWorkingDays,
                'recurring_blocks' => $staffMember->recurringBlocks,
            ]
        ]);
    }

    // ESTA FUNCION ES LA ANTERIOR
    public function update_BKP(Request $request, StaffMember $staffMember): JsonResponse
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

            'recurring_blocks' => ['nullable', 'array'],
            'recurring_blocks.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'recurring_blocks.*.start' => ['required'],
            'recurring_blocks.*.end' => ['required'],
            'recurring_blocks.*.label' => ['nullable', 'string'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | VALIDACIONES
        |--------------------------------------------------------------------------
        */

        // Validar horarios de atención
        foreach ($validated['weekly_schedule'] as $day) {
            if ($day['start_time'] >= $day['end_time']) {
                return response()->json([
                    'message' => 'Horario inválido en agenda'
                ], 422);
            }
        }

        // Agrupar bloques por día
        $blocksByDay = collect($validated['recurring_blocks'] ?? [])
            ->groupBy('day_of_week');

        $scheduleByDay = collect($validated['weekly_schedule'])
            ->keyBy('day_of_week');

        foreach ($blocksByDay as $day => $blocks) {

            // Día debe existir en horario
            if (!isset($scheduleByDay[$day])) {
                return response()->json([
                    'message' => 'Bloque en día no laboral'
                ], 422);
            }

            $workDay = $scheduleByDay[$day];

            // Ordenar bloques
            $sorted = collect($blocks)->sortBy('start')->values();

            foreach ($sorted as $index => $block) {

                // start < end
                if ($block['start'] >= $block['end']) {
                    return response()->json([
                        'message' => 'Bloque con horario inválido'
                    ], 422);
                }

                // dentro del horario laboral
                if (
                    $block['start'] < $workDay['start_time'] ||
                    $block['end'] > $workDay['end_time']
                ) {
                    return response()->json([
                        'message' => 'Bloque fuera de horario laboral'
                    ], 422);
                }

                // validar encimados
                if ($index < count($sorted) - 1) {
                    $next = $sorted[$index + 1];

                    if ($block['end'] > $next['start']) {
                        return response()->json([
                            'message' => 'Bloques encimados'
                        ], 422);
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | GUARDAR
        |--------------------------------------------------------------------------
        */

        // Settings
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

        // Weekly schedule
        $staffMember->schedules()->delete();

        foreach ($validated['weekly_schedule'] as $day) {
            $staffMember->schedules()->create([
                'day_of_week' => $day['day_of_week'],
                'start_time' => $day['start_time'],
                'end_time' => $day['end_time'],
            ]);
        }

        // Non working days
        $staffMember->nonWorkingDays()->delete();

        foreach ($validated['non_working_days'] ?? [] as $day) {
            $staffMember->nonWorkingDays()->create([
                'date' => $day['date'],
                'reason' => $day['reason'] ?? null,
            ]);
        }

        // Recurring blocks
        $staffMember->recurringBlocks()->delete();

        foreach ($validated['recurring_blocks'] ?? [] as $block) {
            $staffMember->recurringBlocks()->create([
                'day_of_week' => $block['day_of_week'],
                'start_time' => $block['start'],
                'end_time' => $block['end'],
                'label' => $block['label'] ?? null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        $staffMember->load([
            'agendaSetting',
            'schedules',
            'nonWorkingDays',
            'recurringBlocks'
        ]);

        return response()->json([
            'message' => 'Agenda updated successfully',
            'data' => [
                'settings' => $staffMember->agendaSetting,
                'weekly_schedule' => $staffMember->schedules,
                'non_working_days' => $staffMember->nonWorkingDays,
                'recurring_blocks' => $staffMember->recurringBlocks,
            ]
        ]);
    }


    // nueva funcion para bloqueos individuales - tiene fallas
    public function storeBlock(Request $request, StaffMember $staffMember)
    {
        $this->authorizeAccess($request, $staffMember);

        $validated = $request->validate([
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after:start'],
            'reason' => ['required', 'string', 'max:60'],
        ]);

        $start = Carbon::parse($validated['start_datetime']);
        $end   = Carbon::parse($validated['end_datetime']);

        // Validaciones de Horarios
        try {
            $this->availabilityService->validateOrFail(
                $staffMember->id,
                $start,
                $end
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => AvailabilityErrorHelper::map($e->getMessage()),
                'reason' => $e->getMessage()
            ], 422);
        }

        $block = $staffMember->blockedSlots()->create([
            'organization_id' => $staffMember->organization_id,
            'start_datetime' => $validated['start_datetime'],
            'end_datetime' => $validated['end_datetime'],
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Bloque creado',
            'data' => $block
        ]);
    }

    public function updateBlock(Request $request, StaffMember $staffMember, BlockedSlot $block)
    {
        $this->authorizeAccess($request, $staffMember);

        if ($block->staff_member_id !== $staffMember->id) {
            abort(403);
        }

        $validated = $request->validate([
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after:start'],
            'reason' => ['required', 'string', 'max:60'],
        ]);

        $start = Carbon::parse($validated['start_datetime']);
        $end   = Carbon::parse($validated['end_datetime']);

        try {
            $this->availabilityService->validateOrFail(
                $staffMember->id,
                $start,
                $end,
                $block->id // IGNORAR ESTE BLOQUE
            );
        } catch (\Exception $e) {

            return response()->json([
                'message' => AvailabilityErrorHelper::map($e->getMessage()),
                'reason' => $e->getMessage()
            ], 422);
        }


        $block->update([
            'start_datetime' => $validated['start_datetime'],
            'end_datetime' => $validated['end_datetime'],
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Bloque actualizado',
            'data' => $block
        ]);
    }

    public function deleteBlock(Request $request, StaffMember $staffMember, BlockedSlot $block)
    {
        $this->authorizeAccess($request, $staffMember);

        if ($block->staff_member_id !== $staffMember->id) {
            abort(403);
        }

        $block->delete();

        return response()->json(['message' => 'Bloque eliminado']);
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
