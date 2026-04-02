<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | Pricing snapshot
            |--------------------------------------------------------------------------
            */
            $table->decimal('base_price', 8, 2) // precio del servicio en ese momento
                ->after('service_variant_id');

            $table->decimal('discount_amount', 8, 2) // cuánto se descontó
                ->default(0)
                ->after('base_price');

            $table->decimal('final_price', 8, 2) // lo que realmente se cobra
                ->after('discount_amount');


            /*
            |--------------------------------------------------------------------------
            | Deposit (anticipo)
            |--------------------------------------------------------------------------
            */
            $table->decimal('deposit_amount', 8, 2) // cuánto debía pagar de anticipo
                ->nullable()
                ->after('final_price');

            $table->enum('deposit_status', ['not_required', 'pending', 'paid']) // estado del anticipo
                ->default('not_required')
                ->after('deposit_amount');


            /*
            |--------------------------------------------------------------------------
            | Payment (total)
            |--------------------------------------------------------------------------
            */
            $table->enum('payment_status', ['pending', 'partial', 'paid']) // si ya pagó todo o no
                ->default('pending')
                ->after('deposit_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'base_price',
                'discount_amount',
                'final_price',
                'deposit_amount',
                'deposit_status',
                'payment_status',
            ]);
        });
    }
};
