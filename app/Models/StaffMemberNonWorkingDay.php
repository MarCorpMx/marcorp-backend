<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMemberNonWorkingDay extends Model
{
    use HasFactory;

    protected $table = 'staff_member_non_working_days';

    protected $fillable = [
        'staff_member_id',
        'date',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }
}