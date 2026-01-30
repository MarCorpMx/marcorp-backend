<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OrganizationUserSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();
        $users = User::take(3)->get();

        if (!$organization || $users->isEmpty()) {
            return;
        }

        // Owner
        OrganizationUser::firstOrCreate([
            'organization_id' => $organization->id,
            'user_id' => $users[0]->id,
        ], [
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        // Admin
        if (isset($users[1])) {
            OrganizationUser::firstOrCreate([
                'organization_id' => $organization->id,
                'user_id' => $users[1]->id,
            ], [
                'role' => 'admin',
                'status' => 'active',
                'joined_at' => now(),
            ]);
        }

        // Staff invitado
        if (isset($users[2])) {
            OrganizationUser::firstOrCreate([
                'organization_id' => $organization->id,
                'user_id' => $users[2]->id,
            ], [
                'role' => 'staff',
                'status' => 'invited',
                'invited_at' => now(),
            ]);
        }
    }
}
