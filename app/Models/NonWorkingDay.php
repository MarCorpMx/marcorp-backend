<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NonWorkingDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'professional_id',
        'date',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
