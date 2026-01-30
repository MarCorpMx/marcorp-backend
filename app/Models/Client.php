<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'phone' => 'array',
    ];

    
    /* =====================
     | Relaciones
     ===================== */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
