<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Branch extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'reference_prefix',
        'is_active',
        'is_primary',

        // contacto
        'phone',
        'email',
        'website',

        // ubicación
        'country',
        'state',
        'city',
        'zip_code',
        'address',

        // branding
        'theme_key',
        'primary_color',
        'secondary_color',
        'logo_url',
        'white_label',

        // dominio
        'primary_domain',
        'domains',
        'force_https',

        // sistema
        'timezone',
        'metadata',
    ];

    protected $casts = [
        'phone' => 'array',
        'domains' => 'array',
        'metadata' => 'array',

        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'white_label' => 'boolean',
        'force_https' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function staff()
    {
        return $this->belongsToMany(
            StaffMember::class,
            'branch_staff',
            'branch_id',
            'staff_member_id'
        )->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | FALLBACKS AUTOMÁTICOS
    |--------------------------------------------------------------------------
    */

    public function getPhoneAttribute($value)
    {
        return $value ?? $this->organization?->phone;
    }

    public function getEmailAttribute($value)
    {
        return $value ?? $this->organization?->email;
    }

    public function getWebsiteAttribute($value)
    {
        return $value ?? $this->organization?->website;
    }

    public function getAddressAttribute($value)
    {
        return $value ?? $this->organization?->address;
    }

    public function getCityAttribute($value)
    {
        return $value ?? $this->organization?->city;
    }

    public function getStateAttribute($value)
    {
        return $value ?? $this->organization?->state;
    }

    public function getCountryAttribute($value)
    {
        return $value ?? $this->organization?->country;
    }

    public function getZipCodeAttribute($value)
    {
        return $value ?? $this->organization?->zip_code;
    }

    public function getPrimaryColorAttribute($value)
    {
        return $value ?? $this->organization?->primary_color;
    }

    public function getSecondaryColorAttribute($value)
    {
        return $value ?? $this->organization?->secondary_color;
    }

    public function getLogoUrlAttribute($value)
    {
        return $value ?? $this->organization?->logo_url;
    }

    public function getReferencePrefixAttribute($value)
    {
        return $value ?? $this->organization?->reference_prefix;
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function primaryPhone()
    {
        return $this->phone[0]['e164Number'] ?? null;
    }

    public function hasCustomPhone()
    {
        return !is_null($this->getRawOriginal('phone'));
    }

    public function displayName()
    {
        return $this->name . ' (' . ($this->city ?? 'N/A') . ')';
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::creating(function ($branch) {
            if (!$branch->slug) {
                $branch->slug = Str::slug($branch->name);
            }
        });

        static::saving(function ($branch) {
            if ($branch->is_primary) {
                self::where('organization_id', $branch->organization_id)
                    ->where('id', '!=', $branch->id)
                    ->update(['is_primary' => false]);
            }
        });
    }
}
