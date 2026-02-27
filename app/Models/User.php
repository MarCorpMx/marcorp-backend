<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'name',
        'username',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'address',
        'status',
        'role',
        'company',
        'email_verified',
        'subsystem_id',
    ];

    /**
     * Los atributos que deben ocultarse en serialización.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts de atributos.
     */
    protected $casts = [
        'email_verified' => 'boolean',
        'phone' => 'array',
        'address' => 'array',
        'last_login_at' => 'datetime',
    ];

    /**
     *  Generar name automáticamente
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            // Si no viene name, lo generamos automáticamente
            if (empty($user->name)) {
                $user->name = trim("{$user->first_name} {$user->last_name}");
            }
        });

        static::updating(function ($user) {
            // Si cambian nombres, actualizamos name también
            if ($user->isDirty(['first_name', 'last_name'])) {
                $user->name = trim("{$user->first_name} {$user->last_name}");
            }
        });
    }

    /**
     *  Relación con sistemas del usuario
     */
    public function subsystems()
    {
        return $this->belongsToMany(Subsystem::class, 'user_subsystems')
            ->withPivot(['is_paid'])
            ->withTimestamps();
    }

    public function userSubsystems()
    {
        return $this->hasMany(UserSubsystem::class);
    }

    /****************************************** */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
            ->withPivot(['role', 'status', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    public function currentOrganization()
    {
        return $this->organizations()
            ->wherePivot('status', 'active')
            ->first();
    }

    public function clientNotes()
    {
        return $this->hasMany(ClientNote::class, 'author_id');
    }
}
