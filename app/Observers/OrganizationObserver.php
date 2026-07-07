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
        //Branch::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'is_primary' => true,
            ],
            [
                'created_by' => $organization->owner_user_id,
                //'name' => 'Sucursal Principal',
                //'name' => $organization->name,
                'name' => 'Principal',
                //'slug' => $organization->slug . '-principal',
                'slug' => 'principal',
                //'reference_prefix' => $organization->reference_prefix,
                'is_active' => true,

                // Herencia inteligente (solo base mínima)
                'timezone' => $organization->timezone,

                'phone' => $organization->phone,
                'email' => $organization->email,
                'website' => $organization->website,

                'country' => $organization->country,
                'state' => $organization->state,
                'city' => $organization->city,
                'zip_code' => $organization->zip_code,
                'address' => $organization->address,

                'primary_color' => $organization->primary_color,
                'secondary_color' => $organization->secondary_color,
                'logo_url' => $organization->getRawOriginal('logo_url'),

                'theme_key' => $organization->theme_key,
            ]
        );
    }

    public function updated(Organization $organization): void {}
    public function deleted(Organization $organization): void {}
    public function restored(Organization $organization): void {}
    public function forceDeleted(Organization $organization): void {}
}
