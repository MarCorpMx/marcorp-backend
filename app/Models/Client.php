<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;
    
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

    public function notes()
    {
        return $this->hasMany(ClientNote::class);
    }
}
