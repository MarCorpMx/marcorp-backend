<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();

        if (!$organization) {
            return;
        }

        $datosJsonTel = [
            'Tel1' => '777 123 1345',
            'Tel2' => 'prueba',
            'Tel3' => 'prueba'
        ];

        Client::create([
            'organization_id' => $organization->id,
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'phone' => json_encode($datosJsonTel),
            'email' => 'juan.perez@demo.com',
            'notes' => 'Cliente de prueba',
            'is_active' => true,
        ]);

        Client::create([
            'organization_id' => $organization->id,
            'first_name' => 'María',
            'last_name' => 'López',
            'phone' => json_encode($datosJsonTel),
            'email' => 'maria.lopez@demo.com',
            'notes' => 'Cliente frecuente',
            'is_active' => true,
        ]);
    }
}
