<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Organization;
use App\Models\User;
use App\Models\Client;
use App\Models\Appointment;
use App\Models\ClientNote;

use Illuminate\Support\Str;
use App\Models\ServiceVariant;
use App\Models\StaffMember;
use App\Models\Service;

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

            $clients = Client::factory()
                ->count(40)
                ->create([
                    'organization_id' => $organization->id,
                    'created_by' => $owner->id ?? null,
                ]);

            /*
            |--------------------------------------------------------------------------
            | 2️⃣ Ajustar tipos especiales
            |--------------------------------------------------------------------------
            */

            $newClients = $clients->take(5);
            $inactiveClients = $clients->slice(5, 5);
            $noAppointmentClients = $clients->slice(10, 5);
            $cancelledHeavyClients = $clients->slice(15, 10);
            $normalClients = $clients->slice(25);

            // Clientes nuevos (últimos 7 días)
            foreach ($newClients as $client) {
                $client->update([
                    'created_at' => Carbon::now()->subDays(rand(0, 6)),
                ]);
            }

            // Clientes inactivos
            foreach ($inactiveClients as $client) {
                $client->update([
                    'is_active' => false,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 3️⃣ Crear citas
            |--------------------------------------------------------------------------
            */

            $serviceVariants = ServiceVariant::whereHas('service', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })->where('active', true)->get();

            $staffMembers = StaffMember::where('organization_id', $organization->id)->get();

            if ($serviceVariants->isEmpty()) {
                throw new \Exception('No hay service_variants activas para esta organización.');
            }

            foreach ($clients as $client) {

                if ($noAppointmentClients->contains($client)) {
                    continue;
                }

                $pastAppointments = rand(1, 6);
                $futureAppointments = rand(0, 2);

                // ----------------------
                // Citas pasadas
                // ----------------------
                for ($i = 0; $i < $pastAppointments; $i++) {

                    $variant = $serviceVariants->random();
                    $staff = $staffMembers->isNotEmpty() ? $staffMembers->random() : null;

                    $date = Carbon::now()
                        ->subDays(rand(5, 200))
                        ->setTime(rand(9, 18), 0);

                    $status = 'confirmed';

                    if ($cancelledHeavyClients->contains($client) && rand(1, 3) === 1) {
                        $status = 'cancelled';
                    }

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
                        'source' => 'admin_panel',

                        'notes' => fake()->sentence(),

                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }

                // ----------------------
                // Citas futuras
                // ----------------------
                for ($i = 0; $i < $futureAppointments; $i++) {

                    $variant = $serviceVariants->random();
                    $staff = $staffMembers->isNotEmpty() ? $staffMembers->random() : null;

                    $date = Carbon::now()
                        ->addDays(rand(3, 45))
                        ->setTime(rand(9, 18), 0);

                    Appointment::create([
                        'uuid' => Str::uuid(),

                        'organization_id' => $organization->id,
                        'service_variant_id' => $variant->id,
                        'staff_member_id' => $staff?->id,
                        'client_id' => $client->id,

                        'start_datetime' => $date,
                        'end_datetime' => (clone $date)->addMinutes($variant->duration_minutes),

                        'capacity_reserved' => rand(1, $variant->max_capacity),

                        'status' => 'confirmed',
                        'source' => 'admin_panel',

                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 4️⃣ Crear notas clínicas
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
                        'is_private' => rand(1, 4) === 1, // 25% privadas
                        'created_at' => Carbon::now()->subDays(rand(1, 200)),
                    ]);
                }
            }
        });
    }
}
