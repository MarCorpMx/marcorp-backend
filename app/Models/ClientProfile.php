<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientProfile extends Model
{
    protected $fillable = [
        'organization_id',
        'client_id',
        'profile_data',
        'preferences',
        'consents',
        'internal_flags',
        'metadata',
    ];

    protected $casts = [
        'profile_data' => 'array',
        'preferences' => 'array',
        'consents' => 'array',
        'internal_flags' => 'array',
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
        return $this->belongsTo(Client::class);
    }
}
