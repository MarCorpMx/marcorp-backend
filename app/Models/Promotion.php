<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Constantes
    |--------------------------------------------------------------------------
    */
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENTAGE = 'percentage';


    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'organization_id',
        'service_id',
        'service_variant_id',
        'name',
        'discount_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'is_active',
    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */
    protected $casts = [
        'discount_value' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceVariant()
    {
        return $this->belongsTo(ServiceVariant::class);
    }


    /*
    |--------------------------------------------------------------------------
    | Lógica útil (PRO)
    |--------------------------------------------------------------------------
    */

    // Saber si está activa en este momento
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }


    // Calcular descuento
    public function calculateDiscount(float $basePrice): float
    {
        if ($this->discount_type === self::TYPE_FIXED) {
            return min($this->discount_value, $basePrice);
        }

        if ($this->discount_type === self::TYPE_PERCENTAGE) {
            return ($basePrice * $this->discount_value) / 100;
        }

        return 0;
    }
}
