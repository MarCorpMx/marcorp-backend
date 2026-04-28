<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'organization_id',
        'created_by',
        'updated_by',

        'first_name',
        'last_name',
        'preferred_name',

        'email',
        'email_verified_at',
        'phone',

        'birth_date',
        'gender',
        'preferred_language',
        'timezone',

        'source',
        'tags',
        'notes',

        'last_visit_at',
        'last_booking_at',

        'is_active',
        'is_blocked',
        'blocked_reason',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'phone' => 'array',
        'tags' => 'array',

        'birth_date' => 'date',

        'email_verified_at' => 'datetime',
        'last_visit_at' => 'datetime',
        'last_booking_at' => 'datetime',

        'is_active' => 'boolean',
        'is_blocked' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Appends
    |--------------------------------------------------------------------------
    */

    protected $appends = [
        'full_name',
        'display_name',
        'age',
        'phone_number',
    ];

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute(): string
    {
        return trim(
            collect([$this->first_name, $this->last_name])
                ->filter()
                ->implode(' ')
        );
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferred_name ?: $this->full_name;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    public function getPhoneNumberAttribute(): ?string
    {
        return $this->phone['internationalNumber']
            ?? $this->phone['number']
            ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function isBlocked(): bool
    {
        return $this->is_blocked === true;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('is_blocked', false);
    }

    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeVerifiedEmail($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {

            $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('preferred_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone->number', 'like', "%{$term}%")
                ->orWhere('phone->nationalNumber', 'like', "%{$term}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function notes()
    {
        return $this->hasMany(ClientNote::class);
    }
}
