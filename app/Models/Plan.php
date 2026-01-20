<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'price',
    ];

    public function userPlans()
    {
        return $this->hasMany(UserPlan::class);
    }
}
