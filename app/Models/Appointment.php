<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use SoftDeletes;

    const DEPOSIT_NOT_REQUIRED = 'not_required';
    const DEPOSIT_PENDING = 'pending';
    const DEPOSIT_PAID = 'paid';

    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PARTIAL = 'partial';
    const PAYMENT_PAID = 'paid';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'branch_service_variant_id',
        'staff_member_id',
        'client_id',
        'pet_id',
        'appointment_series_id',

        'start_datetime',
        'end_datetime',

        'is_exception',
        'original_start_datetime',

        'status',
        'source',
        'notes',
        'mode',

        'reference_code',

        'base_price',
        'discount_amount',
        'final_price',

        'deposit_amount',
        'deposit_status',

        'payment_status',

        'capacity_reserved',
        'timezone',
        'rescheduled_by',
        'meeting_url',
        'meeting_provider',
        'meeting_id',
        
        'branch_snapshot',

        'created_by',
        'updated_by',

        'rescheduled_source',
        'rescheduled_at',

        'cancelled_by',
        'cancelled_at',
        'cancellation_source',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',

        'original_start_datetime' => 'datetime',

        'rescheduled_at' => 'datetime',
        'cancelled_at' => 'datetime',

        'is_exception' => 'boolean',

        'base_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',

        'branch_snapshot' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function series()
    {
        return $this->belongsTo(AppointmentSeries::class, 'appointment_series_id');
    }

    /*public function branchServiceVariant(): BelongsTo
    {
        return $this->belongsTo(
            BranchServiceVariant::class,
            'branch_service_variant_id'
        );
    }*/

    public function branchServiceVariant(): BelongsTo
    {
        return $this->belongsTo(
            BranchServiceVariant::class,
            'branch_service_variant_id'
        )->withTrashed();
    }

    public function serviceVariant(): BelongsTo
    {
        return $this->branchServiceVariant();
    }


    public function staff(): BelongsTo
    {
        return $this->belongsTo(
            StaffMember::class,
            'staff_member_id'
        );
    }

    public function client()
    {
        return $this->belongsTo(
            Client::class,
            'client_id'
        );
    }

    public function pet()
    {
        return $this->belongsTo(
            ClientPet::class,
            'pet_id'
        );
    }

    public function appointmentNotes()
    {
        return $this->hasMany(AppointmentNote::class)->latest();
    }

    public function actionTokens()
    {
        return $this->hasMany(AppointmentActionToken::class);
    }

    public function isFullyPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    protected static function booted()
    {
        static::creating(function ($appointment) {

            $appointment->uuid = Str::uuid();

            $organization = Organization::find(
                $appointment->organization_id
            );

            $prefix = $organization?->reference_prefix
                ? strtoupper($organization->reference_prefix)
                : null;

            do {
                $random =
                    strtoupper(Str::random(4))
                    . '-'
                    . strtoupper(Str::random(4));

                $reference = $prefix
                    ? "{$prefix}-" . now()->format('ym') . "-{$random}"
                    : now()->format('ym') . "-{$random}";
            } while (
                self::where(
                    'reference_code',
                    $reference
                )->exists()
            );

            $appointment->reference_code = $reference;
        });
    }
}
