<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientBranch extends Model
{
    protected $fillable = [
        'organization_id',
        'client_id',
        'branch_id',
        'first_visit_at',
        'last_visit_at',
        'appointments_count',
        'is_primary',
        'metadata',
    ];

    protected $casts = [
        'first_visit_at' => 'datetime',
        'last_visit_at' => 'datetime',
        'appointments_count' => 'integer',
        'is_primary' => 'boolean',
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

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
