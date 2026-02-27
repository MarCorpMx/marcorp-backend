<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'name',
        'email',
        'is_active'
    ];

    public function services()
    {
        return $this->belongsToMany(Service::class);
    }

    /*public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }*/

    public function serviceVariants()
    {
        return $this->belongsToMany(
            ServiceVariant::class,
            'service_variant_staff',
            'staff_id',
            'service_variant_id'
        )->withTimestamps();
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'staff_id');
    }
}
