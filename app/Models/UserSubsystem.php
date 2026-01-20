<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubsystem extends Model
{
    use HasFactory;

    protected $table = 'user_subsystems';

    protected $fillable = [
        'user_id',
        'subsystem_id',
        'is_paid'
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_subsystem_roles');
    }

    public function subsystem()
    {
        return $this->belongsTo(Subsystem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
