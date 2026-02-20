<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationMailSetting extends Model
{
    protected $fillable = [
        'organization_id',
        'provider',        // 🔥 nuevo
        'mailer',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
        'is_active',
        'priority',        // 🔥 nuevo
    ];

    protected $casts = [
        'password' => 'encrypted', // 🔐 MUY IMPORTANTE
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
