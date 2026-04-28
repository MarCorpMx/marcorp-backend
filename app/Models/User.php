<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
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
        'company',
        'subsystem_id',
    ];

    /**
     * Los atributos que deben ocultarse en serialización.
     */
    protected $hidden = [
        //'password',
        'email_verified_at' => 'datetime',
        'last_verification_sent_at' => 'datetime',
        'phone' => 'array',
        'address' => 'array',
        'last_login_at' => 'datetime',
    ];

    /**
     * Casts de atributos.
     */
    protected $casts = [
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
     *  Relación con roles del usuario
     */

    // rombi verificar como jalaba esa funcion
    /*public function subsystemRoles()
    {
        return $this->hasMany(UserSubsystemRole::class);
    }*/


    /****************************************** */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
            ->withPivot(['status', 'invited_at', 'joined_at'])
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

    public function staff()
    {
        return $this->hasOne(StaffMember::class);
    }

    public function branchAccess()
    {
        return $this->hasMany(BranchUserAccess::class);
    }
}
