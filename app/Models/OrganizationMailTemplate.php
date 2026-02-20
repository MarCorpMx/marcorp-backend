<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationMailTemplate extends Model
{
    protected $fillable = [
        'organization_id',
        'type',
        'name',
        'subject',
        'body_html',
        'body_text',
        'is_active'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}

