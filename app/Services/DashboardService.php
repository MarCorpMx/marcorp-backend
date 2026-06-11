<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\BranchServiceVariant;
use App\Models\Branch;

use Carbon\Carbon;

class DashboardService
{
    public function getData(Branch $branch): array
    {
        $timezone = $branch->timezone ?: 'America/Mexico_City';

        /*
        |--------------------------------------------------------------------------
        | Rango de HOY en timezone local
        |--------------------------------------------------------------------------
        */

        $todayStartLocal = Carbon::now($timezone)->startOfDay();
        $todayEndLocal = Carbon::now($timezone)->endOfDay();

        $todayStartUtc = $todayStartLocal
            ->copy()
            ->utc();

        $todayEndUtc = $todayEndLocal
            ->copy()
            ->utc();

        /*
        |--------------------------------------------------------------------------
        | Citas de hoy
        |--------------------------------------------------------------------------
        */

        $appointmentsTodayQuery = Appointment::query()
            ->where('organization_id', $branch->organization_id)
            ->where('branch_id', $branch->id)
            ->whereBetween(
                'start_datetime',
                [$todayStartUtc, $todayEndUtc]
            );

        $appointmentsToday = (clone $appointmentsTodayQuery)->count();

        $incomeToday = (clone $appointmentsTodayQuery)
            ->whereIn('payment_status', [
                Appointment::PAYMENT_PARTIAL,
                Appointment::PAYMENT_PAID,
            ])
            ->sum('final_price');

        $noShowToday = (clone $appointmentsTodayQuery)
            ->where('status', 'no_show')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Próxima cita
        |--------------------------------------------------------------------------
        */

        $nextAppointment = Appointment::query()
            ->with([
                'client',
                'branchServiceVariant.branchService'
            ])
            ->where('organization_id', $branch->organization_id)
            ->where('branch_id', $branch->id)
            ->whereIn('status', [
                'pending',
                'confirmed'
            ])
            ->where(
                'start_datetime',
                '>=',
                now()->utc()
            )
            ->orderBy('start_datetime')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Agenda de hoy
        |--------------------------------------------------------------------------
        */

        $todayAppointments = Appointment::query()
            ->with([
                'client',
                'branchServiceVariant.branchService'
            ])
            ->where('organization_id', $branch->organization_id)
            ->where('branch_id', $branch->id)
            ->whereBetween(
                'start_datetime',
                [$todayStartUtc, $todayEndUtc]
            )
            ->orderBy('start_datetime')
            ->get()
            ->map(function ($appointment) use ($timezone) {

                return [

                    'id' => $appointment->id,

                    'time' => $appointment
                        ->start_datetime
                        ->copy()
                        ->timezone($timezone)
                        ->format('H:i'),

                    'start_datetime' =>
                    $appointment->start_datetime,

                    'client_name' =>
                    $appointment->client?->display_name,

                    'service_name' =>
                    $this->getServiceLabel($appointment),

                    'status' =>
                    $appointment->status,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Totales
        |--------------------------------------------------------------------------
        */

        $clientsTotal = Client::query()
            ->where(
                'organization_id',
                $branch->organization_id
            )
            ->count();

        $servicesTotal = BranchServiceVariant::query()
            ->where(
                'organization_id',
                $branch->organization_id
            )
            ->where(
                'branch_id',
                $branch->id
            )
            ->where('active', true)
            ->count();

        return [

            'kpis' => [

                'appointments_today' =>
                $appointmentsToday,

                'income_today' =>
                (float) $incomeToday,

                'no_show_today' =>
                $noShowToday,

                'clients_total' =>
                $clientsTotal,

                'services_total' =>
                $servicesTotal,
            ],

            'next_appointment' => $nextAppointment
                ? [

                    'id' =>
                    $nextAppointment->id,

                    'time' =>
                    $nextAppointment
                        ->start_datetime
                        ->copy()
                        ->timezone($timezone)
                        ->format('H:i'),

                    'start_datetime' =>
                    $nextAppointment->start_datetime,

                    'client_name' =>
                    $nextAppointment->client?->display_name,

                    'service_name' =>
                    $this->getServiceLabel($nextAppointment),

                    'status' =>
                    $nextAppointment->status,
                ]
                : null,

            'today_appointments' =>
            $todayAppointments,

            'recent_activity' => []
        ];
    }

    // Obtener nombre del servicio
    protected function getServiceLabel(
        Appointment $appointment
    ): string {

        $service = $appointment
            ->branchServiceVariant?->branchService?->name;

        $variant = $appointment
            ->branchServiceVariant?->name;

        if ($service && $variant) {
            return "{$service} - {$variant}";
        }

        if ($service) {
            return $service;
        }

        if ($variant) {
            return $variant;
        }

        return 'Servicio';
    }
}
