<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientPet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'client_id',

        'name',
        'species',
        'breed',
        'gender',

        'weight',
        'weight_unit',
        'color',

        'birth_date',

        'allergies',
        'medical_notes',

        'photo_url',

        'status',

        'metadata',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'weight' => 'decimal:2',
        'metadata' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function client()
    {
        return $this->belongsTo(
            Client::class,
            'client_id'
        );
    }

    public function appointments()
    {
        return $this->hasMany(
            Appointment::class,
            'pet_id'
        );
    }
}
