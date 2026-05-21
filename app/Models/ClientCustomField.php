<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientCustomField extends Model
{
    protected $fillable = [
        'organization_id',

        'key',
        'label',
        'field_type',

        'placeholder',
        'help_text',

        'is_required',
        'is_active',
        'is_visible',

        'options',

        'validation_rules',

        'group_name',
        'sort_order',

        'metadata',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',

        'options' => 'array',
        'validation_rules' => 'array',
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

    public function values()
    {
        return $this->hasMany(ClientCustomFieldValue::class);
    }
}
