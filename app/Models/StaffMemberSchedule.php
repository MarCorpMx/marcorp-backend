<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMemberSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_member_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
        //'start_time' => 'datetime:H:i',
        //'end_time' => 'datetime:H:i',
        'start_time' => 'string',
        'end_time'   => 'string',
    ];

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }
}
