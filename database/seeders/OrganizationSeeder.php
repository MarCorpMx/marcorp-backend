<?php
namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::first();

        if (!$owner) {
            return;
        }

        $datosJsonTel = [
            'Tel1' => '770 202 1345',
            'Tel2' => 'prueba',
            'Tel3' => 'prueba'
        ];

        Organization::create([
            'name' => 'Organización Demo',
            'slug' => Str::slug('Organización Demo'),
            'owner_user_id' => $owner->id,
            'status' => 'active',
            'email' => 'contacto@demo.com',
            'phone' => json_encode($datosJsonTel),
            'metadata' => [
                'plan' => 'free',
                'timezone' => 'America/Mexico_City',
            ],
        ]);
    }
}
