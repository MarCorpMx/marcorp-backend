<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'user_id',

        'first_name',
        'last_name',
        'name',
        'slug',

        'email',
        'phone',

        'title',
        'specialty',
        'bio',

        'avatar',

        'is_active',
        'is_public',
        'accepts_online',
        'accepts_presential',

        'settings',
    ];

    protected $casts = [
        'phone' => 'array',
        'settings' => 'array',

        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'accepts_online' => 'boolean',
        'accepts_presential' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Organización a la que pertenece
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }


    public function branches()
    {
        return $this->belongsToMany(
            Branch::class,
            'branch_staff',
            'staff_id',
            'branch_id'
        )->withTimestamps();
    }

    public function belongsToBranch($branchId): bool
    {
        return $this->branches()
            ->where('branch_id', $branchId)
            ->exists();
    }

    // Usuario del sistema (si aplica)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Citas asignadas
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }


    // Variantes específicas que puede ofrecer
    public function serviceVariants()
    {
        return $this->belongsToMany(
            ServiceVariant::class,
            'service_variant_staff',
            'staff_member_id',
            'service_variant_id'
        )->withPivot('branch_id')
            ->withTimestamps();
    }

    // Horarios semanales
    public function schedules()
    {
        return $this->hasMany(StaffMemberSchedule::class);
    }

    // Configuración de agenda (1 a 1)
    public function agendaSettings()
    {
        return $this->hasMany(StaffMemberAgendaSetting::class);
    }

    // Días no laborables
    public function nonWorkingDays()
    {
        return $this->hasMany(StaffMemberNonWorkingDay::class);
    }

    // Bloqueos manuales
    public function blockedSlots()
    {
        return $this->hasMany(BlockedSlot::class);
    }

    // Bloqueos recurrentes
    public function recurringBlocks()
    {
        return $this->hasMany(StaffRecurringBlock::class);
    }

    protected static function booted()
    {
        static::creating(function ($staff) {

            // Si no viene first_name → lo sacamos de name
            if (empty($staff->first_name) && !empty($staff->name)) {
                $parts = explode(' ', trim($staff->name));

                $staff->first_name = $parts[0] ?? '';
                $staff->last_name = isset($parts[1])
                    ? implode(' ', array_slice($parts, 1))
                    : null;
            }

            // Generar name si no existe
            if (empty($staff->name)) {
                $staff->name = trim("{$staff->first_name} {$staff->last_name}");
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function accesses()
    {
        return $this->hasMany(BranchUserAccess::class, 'staff_member_id');
    }



    public function agendaSettingForBranch($branchId)
    {
        return $this->agendaSettings()
            ->where('branch_id', $branchId)
            ->first();
    }

    public function schedulesForBranch($branchId)
    {
        return $this->schedules()
            ->where('branch_id', $branchId);
    }

    public function nonWorkingDaysForBranch($branchId)
    {
        return $this->nonWorkingDays()
            ->where('branch_id', $branchId);
    }

    public function recurringBlocksForBranch($branchId)
    {
        return $this->recurringBlocks()
            ->where('branch_id', $branchId);
    }
}
