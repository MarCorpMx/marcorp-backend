<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchStaff extends Model
{
    protected $table = 'branch_staff';

    protected $fillable = [
        'branch_id',
        'staff_member_id',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function staff()
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }
}
