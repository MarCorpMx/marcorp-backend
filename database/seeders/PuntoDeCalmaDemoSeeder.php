<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

use App\Models\Organization;
use App\Models\User;
use App\Models\Client;
use App\Models\Appointment;
use App\Models\ClientNote;
use App\Models\ServiceVariant;
use App\Models\StaffMember;

class PuntoDeCalmaDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $organization = Organization::where('slug', 'punto-de-calma')->firstOrFail();
            $owner = User::where('email', 'contacto@punto-de-calma.com')->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | 1️⃣ Crear 40 clientes base
            |--------------------------------------------------------------------------
            */

            $clients = collect();

            for ($i = 0; $i < 40; $i++) {

                $clients->push(
                    Client::create([
                        'organization_id' => $organization->id,
                        'created_by' => $owner->id,

                        'first_name' => fake()->firstName(),
                        'last_name' => fake()->lastName(),
                        'email' => fake()->unique()->safeEmail(),

                        'phone' => [
                            'number' => '777 ' . rand(1000000, 9999999),
                            'internationalNumber' => '+52 777 ' . rand(1000000, 9999999),
                            'nationalNumber' => '777' . rand(1000000, 9999999),
                            'e164Number' => '+52777' . rand(1000000, 9999999),
                            'countryCode' => 'MX',
                            'dialCode' => '+52',
                        ],

                        'birth_date' => Carbon::now()->subYears(rand(18, 65))->subDays(rand(0, 365)),

                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 2️⃣ Segmentación especial
            |--------------------------------------------------------------------------
            */

            $newClients = $clients->take(5);
            $inactiveClients = $clients->slice(5, 5);
            $noAppointmentClients = $clients->slice(10, 5);

            foreach ($newClients as $client) {
                $client->update([
                    'created_at' => Carbon::now()->subDays(rand(0, 6)),
                ]);
            }

            foreach ($inactiveClients as $client) {
                $client->update([
                    'is_active' => false,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 3️⃣ Crear citas con lógica realista
            |--------------------------------------------------------------------------
            */

            $serviceVariants = ServiceVariant::whereHas('service', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })->where('active', true)->get();

            $staffMembers = StaffMember::where('organization_id', $organization->id)->get();

            if ($serviceVariants->isEmpty()) {
                throw new \Exception('No hay service_variants activas.');
            }

            // Distribución ponderada para citas pasadas
            $pastStatusPool = [
                'completed' => 70,
                'no_show' => 10,
                'cancelled' => 10,
                'rescheduled' => 10,
            ];

            $weightedRandom = function (array $weights) {
                $total = array_sum($weights);
                $rand = rand(1, $total);

                foreach ($weights as $key => $weight) {
                    $rand -= $weight;
                    if ($rand <= 0) {
                        return $key;
                    }
                }
            };

            foreach ($clients as $client) {

                if ($noAppointmentClients->contains($client)) {
                    continue;
                }

                $pastAppointments = rand(1, 6);
                $futureAppointments = rand(0, 2);

                /*
                |--------------------------------------------------------------------------
                | 📅 CITAS PASADAS
                |--------------------------------------------------------------------------
                */

                for ($i = 0; $i < $pastAppointments; $i++) {

                    $variant = $serviceVariants->random();
                    $staff = $staffMembers->isNotEmpty() ? $staffMembers->random() : null;

                    $date = Carbon::now()
                        ->subDays(rand(5, 15)) // rombi 200
                        ->setTime(rand(9, 18), 0);

                    $status = $weightedRandom($pastStatusPool);
                    $source = rand(0, 1) ? 'admin_panel' : 'public_web';

                    $appointment = Appointment::create([
                        'uuid' => Str::uuid(),
                        'organization_id' => $organization->id,
                        'service_variant_id' => $variant->id,
                        'staff_member_id' => $staff?->id,
                        'client_id' => $client->id,

                        'start_datetime' => $date,
                        'end_datetime' => (clone $date)->addMinutes($variant->duration_minutes),

                        'capacity_reserved' => rand(1, $variant->max_capacity),

                        'status' => $status,
                        'source' => $source,
                        'notes' => fake()->sentence(),

                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);

                    // Si fue reprogramada → crear nueva futura confirmada
                    if ($status === 'rescheduled') {

                        $newDate = Carbon::now()
                            ->addDays(rand(3, 30))
                            ->setTime(rand(9, 18), 0);

                        Appointment::create([
                            'uuid' => Str::uuid(),
                            'organization_id' => $organization->id,
                            'service_variant_id' => $variant->id,
                            'staff_member_id' => $staff?->id,
                            'client_id' => $client->id,

                            'start_datetime' => $newDate,
                            'end_datetime' => (clone $newDate)->addMinutes($variant->duration_minutes),

                            'capacity_reserved' => 1,

                            'status' => 'confirmed',
                            'source' => $source,

                            'notes' => 'Reprogramada desde cita anterior.',

                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | 🔮 CITAS FUTURAS
                |--------------------------------------------------------------------------
                */

                for ($i = 0; $i < $futureAppointments; $i++) {

                    $variant = $serviceVariants->random();
                    $staff = $staffMembers->isNotEmpty() ? $staffMembers->random() : null;

                    $date = Carbon::now()
                        ->addDays(rand(3, 45))
                        ->setTime(rand(9, 18), 0);

                    $status = rand(1, 4) === 1 ? 'pending' : 'confirmed';
                    $source = rand(0, 1) ? 'admin_panel' : 'public_web';

                    Appointment::create([
                        'uuid' => Str::uuid(),
                        'organization_id' => $organization->id,
                        'service_variant_id' => $variant->id,
                        'staff_member_id' => $staff?->id,
                        'client_id' => $client->id,

                        'start_datetime' => $date,
                        'end_datetime' => (clone $date)->addMinutes($variant->duration_minutes),

                        'capacity_reserved' => rand(1, $variant->max_capacity),

                        'status' => $status,
                        'source' => $source,

                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 4️⃣ Notas clínicas
            |--------------------------------------------------------------------------
            */

            foreach ($clients as $client) {

                $notesCount = rand(1, 5);

                for ($i = 0; $i < $notesCount; $i++) {

                    ClientNote::create([
                        'organization_id' => $organization->id,
                        'client_id' => $client->id,
                        'author_id' => $owner->id,
                        'title' => fake()->sentence(4),
                        'content' => fake()->paragraphs(rand(2, 5), true),
                        'is_private' => rand(1, 4) === 1,
                        'created_at' => Carbon::now()->subDays(rand(1, 15)), // rombi 200
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
}
