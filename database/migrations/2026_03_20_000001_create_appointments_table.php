<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();
            $table->string('reference_code')->nullable()->unique();

            /*
            |--------------------------------------------------------------------------
            | Contexto
            |--------------------------------------------------------------------------
            */
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_service_variant_id')
                ->constrained('branch_service_variant')
                ->cascadeOnDelete();

            $table->foreignId('staff_member_id')
                ->nullable()
                ->constrained('staff_members')
                ->nullOnDelete();

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('pet_id')
                ->nullable()
                ->constrained('client_pets')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Fecha / horario
            |--------------------------------------------------------------------------
            */
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            $table->unsignedInteger('capacity_reserved')->default(1);

            /*
            |--------------------------------------------------------------------------
            | Modalidad
            |--------------------------------------------------------------------------
            */
            $table->string('mode', 50)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Estado cita
            |--------------------------------------------------------------------------
            */
            $table->enum('status', [
                'pending',
                'confirmed',
                'completed',
                'rescheduled',
                'cancelled',
                'no_show'
            ])->default('pending');

            $table->string('rescheduled_by', 30)->nullable();

            $table->enum('source', [
                'public_web',
                'admin_panel'
            ])->default('public_web');

            /*
            |--------------------------------------------------------------------------
            | Pricing snapshot
            |--------------------------------------------------------------------------
            */
            $table->decimal('base_price', 8, 2);

            $table->decimal('discount_amount', 8, 2)
                ->default(0);

            $table->decimal('final_price', 8, 2);

            /*
            |--------------------------------------------------------------------------
            | Anticipo
            |--------------------------------------------------------------------------
            */
            $table->decimal('deposit_amount', 8, 2)
                ->nullable();

            $table->enum('deposit_status', [
                'not_required',
                'pending',
                'paid'
            ])->default('not_required');

            /*
            |--------------------------------------------------------------------------
            | Pago total
            |--------------------------------------------------------------------------
            */
            $table->enum('payment_status', [
                'pending',
                'partial',
                'paid'
            ])->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Notas
            |--------------------------------------------------------------------------
            */
            $table->text('notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Reunión online
            |--------------------------------------------------------------------------
            */
            $table->string('meeting_url')->nullable();
            $table->string('meeting_provider')->nullable();
            $table->string('meeting_id')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */
            $table->index(['organization_id', 'start_datetime']);
            $table->index(['organization_id', 'branch_id']);
            $table->index(['staff_member_id', 'start_datetime', 'end_datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
