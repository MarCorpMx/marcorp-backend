<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Organization;

class Appointment extends Model
{

    /*
    |--------------------------------------------------------------------------
    | Constantes (estados de pagos)
    |--------------------------------------------------------------------------
    */
    const DEPOSIT_NOT_REQUIRED = 'not_required';
    const DEPOSIT_PENDING = 'pending';
    const DEPOSIT_PAID = 'paid';

    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PARTIAL = 'partial';
    const PAYMENT_PAID = 'paid';


    protected $fillable = [
        'organization_id',
        'service_variant_id',
        'staff_member_id',
        'client_id',
        'start_datetime',
        'end_datetime',
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
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',

        'base_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function serviceVariant(): BelongsTo
    {
        return $this->belongsTo(ServiceVariant::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    // Para citas individuales
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // Para citas grupales
    public function attendees(): HasMany
    {
        return $this->hasMany(AppointmentAttendee::class);
    }

    // Notas Internas
    public function appointmentNotes()
    {
        return $this->hasMany(AppointmentNote::class)
            ->latest();
    }

    // Tokens para cuando se confirma/cancela desde email
    public function actionTokens()
    {
        return $this->hasMany(AppointmentActionToken::class);
        // $appointment->actionTokens()->create([...]);
    }

    /******************** */
    public function isFullyPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    protected static function booted()
    {
        static::creating(function ($appointment) {

            $appointment->uuid = Str::uuid();

            $organization = Organization::find($appointment->organization_id);

            $prefix = $organization?->reference_prefix
                ? strtoupper($organization->reference_prefix)
                : null;

            do {
                $random = strtoupper(Str::random(8));

                $reference = $prefix
                    ? "{$prefix}-{$random}"
                    : $random;
            } while (self::where('reference_code', $reference)->exists());

            $appointment->reference_code = $reference;
        });
    }
}
