<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'subsystem_id',
        'key',
        'name',
        'description',
        'price',
        'billing_period',
        'is_active',
    ];

    // 🔗 El plan pertenece a un sistema
    public function subsystem()
    {
        return $this->belongsTo(Subsystem::class);
    }

    // 🔗 Usuarios suscritos a este plan
    public function userPlans()
    {
        return $this->hasMany(UserPlan::class);
    }

    // 🔗 Features habilitadas por plan
    public function features()
    {
        return $this->hasMany(PlanSubsystemFeature::class);
    }
}
