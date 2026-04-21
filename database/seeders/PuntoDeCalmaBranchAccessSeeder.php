<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Organization;
use App\Models\User;
use App\Models\Role;
use App\Models\Subsystem;
use App\Models\Branch;
use App\Models\BranchUserAccess;
use App\Models\StaffMember;
use App\Models\BranchStaff;

class PuntoDeCalmaBranchAccessSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'punto-de-calma')->firstOrFail();
        $user = User::where('email', 'contacto@punto-de-calma.com')->firstOrFail();

        $role = Role::where('key', 'owner')->firstOrFail();
        $subsystem = Subsystem::where('key', 'citas')->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Obtener o crear Staff global
        |--------------------------------------------------------------------------
        */

        $staff = StaffMember::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ],
            [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Asignar a TODAS las sucursales
        |--------------------------------------------------------------------------
        */

        $branches = Branch::where('organization_id', $organization->id)->get();

        foreach ($branches as $branch) {

            // Relación staff - sucursal
            BranchStaff::firstOrCreate([
                'branch_id' => $branch->id,
                'staff_member_id' => $staff->id,
            ]);

            // Acceso con staff_member_id
            BranchUserAccess::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'branch_id' => $branch->id,
                    'subsystem_id' => $subsystem->id,
                ],
                [
                    'role_id' => $role->id,
                    'staff_member_id' => $staff->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
