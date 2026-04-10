<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffRecurringBlock extends Model
{
    protected $fillable = [
        'staff_member_id',
        'day_of_week',
        'start_time',
        'end_time',
        'label'
    ];

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }
}
