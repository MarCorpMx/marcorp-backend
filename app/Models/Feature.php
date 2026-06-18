<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'subsystem_id',

        'key',
        'parent_key',

        'name',
        'description',

        'menu_label',
        'menu_route',
        'menu_icon',
        'sort_order',

        'is_billable',
        'is_core',
        'show_in_plans',

    ];

    public function planSubsystemFeatures()
    {
        return $this->hasMany(PlanSubsystemFeature::class);
    }
}
