<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\Professional;
use App\Models\AgendaSetting;
use App\Models\ProfessionalSchedule;
use App\Models\NonWorkingDay;
use Carbon\Carbon;

class AgendaDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 🔎 Buscar organización real de Michell
        $organization = Organization::where('slug', 'punto-de-calma')->first();

        if (!$organization) {
            $this->command->warn('Organización punto-de-calma no encontrada.');
            return;
        }

        // 👩‍⚕️ Crear profesional (Michell)
        $professional = Professional::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => 'Michell',
            ],
            [
                'specialty' => 'Psicóloga Clínica',
                'color' => '#1FD6A1',
                'is_active' => true,
            ]
        );

        // ⚙️ Configuración de agenda
        AgendaSetting::updateOrCreate(
            ['professional_id' => $professional->id],
            [
                'appointment_duration' => 60,
                'break_between_appointments' => 0,
                'allow_online_booking' => true,
                'minimum_notice_hours' => 4,
                'allow_cancellation' => true,
                'cancellation_limit_hours' => 12,
                'timezone' => 'America/Mexico_City',
            ]
        );

        // 🗓 Horario Lunes a Viernes 10:00 - 18:00
        for ($day = 1; $day <= 5; $day++) {
            ProfessionalSchedule::updateOrCreate(
                [
                    'professional_id' => $professional->id,
                    'day_of_week' => $day,
                ],
                [
                    'start_time' => '10:00',
                    'end_time' => '18:00',
                    'is_active' => true,
                ]
            );
        }

        // ❌ Día no laborable (ejemplo: próxima semana viernes)
        NonWorkingDay::updateOrCreate(
            [
                'professional_id' => $professional->id,
                'date' => Carbon::now()->addWeek()->next(Carbon::FRIDAY)->toDateString(),
            ],
            [
                'reason' => 'Día personal',
            ]
        );

        $this->command->info('Agenda demo para Michell creada correctamente.');


        /* */
        // 👩‍⚕️ Segunda profesional (modo consultorio futuro)
        $professional2 = Professional::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => 'Dra. Margarita Escobar',
            ],
            [
                'specialty' => 'Psicóloga Infantil',
                'color' => '#0FB487',
                'is_active' => true,
            ]
        );

        // Configuración diferente
        AgendaSetting::updateOrCreate(
            ['professional_id' => $professional2->id],
            [
                'appointment_duration' => 45,
                'break_between_appointments' => 15,
                'allow_online_booking' => true,
                'minimum_notice_hours' => 2,
                'allow_cancellation' => true,
                'cancellation_limit_hours' => 8,
                'timezone' => 'America/Mexico_City',
            ]
        );


        // Horarios L–V 9:00–14:00
        for ($day = 1; $day <= 5; $day++) {
            ProfessionalSchedule::updateOrCreate(
                [
                    'professional_id' => $professional2->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00',
                ],
                [
                    'end_time' => '14:00',
                    'is_active' => true,
                ]
            );
        }

        // Sábado 9:00–13:00
        ProfessionalSchedule::updateOrCreate(
            [
                'professional_id' => $professional2->id,
                'day_of_week' => 6,
                'start_time' => '09:00',
            ],
            [
                'end_time' => '13:00',
                'is_active' => true,
            ]
        );

        // Día no laborable distinto
        NonWorkingDay::updateOrCreate(
            [
                'professional_id' => $professional2->id,
                'date' => now()->addWeeks(2)->toDateString(),
            ],
            [
                'reason' => 'Congreso de psicología',
            ]
        );
    }
}
