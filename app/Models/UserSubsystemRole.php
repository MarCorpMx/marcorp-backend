<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubsystemRole extends Model
{
    use HasFactory;

    protected $table = 'user_subsystem_roles';

    protected $fillable = [
        'user_subsystem_id',
        'role_id',
    ];

    public function userSubsystem()
    {
        return $this->belongsTo(UserSubsystem::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
