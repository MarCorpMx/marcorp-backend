<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientCustomFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',

        'client_id',
        'client_custom_field_id',

        'entity_type',
        'entity_id',

        'value',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
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

    public function field()
    {
        return $this->belongsTo(ClientCustomField::class, 'client_custom_field_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isForClient(): bool
    {
        return $this->entity_type === 'client';
    }

    public function isForPet(): bool
    {
        return $this->entity_type === 'pet';
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForEntity($query, string $type, ?int $entityId = null)
    {
        return $query
            ->where('entity_type', $type)
            ->where('entity_id', $entityId);
    }
}
