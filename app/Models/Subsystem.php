<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subsystem extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'is_active',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_subsystems')
            ->withPivot(['is_paid'])
            ->withTimestamps();
    }

    public function userSubsystems()
    {
        return $this->hasMany(UserSubsystem::class);
    }
}
