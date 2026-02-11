<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'uuid',
        'organization_id',
        'client_id',
        'first_name',
        'last_name',
        'email',
        'subject',
        'phone',
        'services',
        'message',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'phone' => 'array',
        'services' => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
