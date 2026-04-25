<?php

namespace App\Observers;

use App\Models\Organization;
use App\Models\Branch;

class OrganizationObserver
{
    /**
     * Handle the Organization "created" event.
     */
    public function created(Organization $organization): void
    {
        Branch::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'is_primary' => true,
            ],
            [
                //'name' => 'Sucursal Principal',
                'name' => $organization->name,
                //'slug' => $organization->slug . '-principal',
                'slug' => 'principal',
                'reference_prefix' => $organization->reference_prefix,
                'is_active' => true,

                // Herencia inteligente (solo base mínima)
                'timezone' => $organization->timezone,
            ]
        );
    }

    public function updated(Organization $organization): void {}
    public function deleted(Organization $organization): void {}
    public function restored(Organization $organization): void {}
    public function forceDeleted(Organization $organization): void {}
}
