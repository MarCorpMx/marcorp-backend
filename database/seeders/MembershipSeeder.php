<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Membership;

class MembershipSeeder extends Seeder
{
    public function run(): void
    {
        $memberships = [
            [
                'key' => 'basic',
                'name' => 'Membresía Básica',
                'description' => 'Acceso estándar al sistema',
                'price' => 0.00,
                'active' => true,
            ],
            [
                'key' => 'premium',
                'name' => 'Membresía Premium',
                'description' => 'Acceso con beneficios adicionales',
                'price' => 499.00,
                'active' => true,
            ],
        ];

        foreach ($memberships as $membership) {
            Membership::updateOrCreate(
                ['key' => $membership['key']],
                $membership
            );
        }
    }
}
