<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubsystem extends Model
{
    use HasFactory;

    protected $table = 'user_subsystem';

    protected $fillable = [
        'user_id',
        'subsystem_id',
        'membership_id',
        'role',
        'active',
        'activated_at',
        'expires_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subsystem()
    {
        return $this->belongsTo(Subsystem::class);
    }

    public function membership()
    {
        return $this->belongsTo(Membership::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_subsystem_roles');
    }
}
