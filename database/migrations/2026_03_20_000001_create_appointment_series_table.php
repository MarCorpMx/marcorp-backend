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
        Schema::create('appointment_series', function (Blueprint $table) {

            $table->id();

            $table->uuid('uuid')->unique();

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

            /*
            |--------------------------------------------------------------------------
            | Regla recurrencia
            |--------------------------------------------------------------------------
            */

            $table->enum('frequency', [
                'daily',
                'weekly',
                'monthly'
            ]);

            $table->unsignedInteger('interval')
                ->default(1);

            /*
            |--------------------------------------------------------------------------
            | Weekly support
            |--------------------------------------------------------------------------
            */

            $table->json('days_of_week')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Inicio / fin
            |--------------------------------------------------------------------------
            */

            $table->dateTime('start_datetime');

            $table->dateTime('until_datetime')
                ->nullable();

            $table->unsignedInteger('occurrences_limit')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')
                ->default(true);

            $table->timestamp('last_generated_at')
                ->nullable(); // Para cron/job

            /*
            |--------------------------------------------------------------------------
            | Metadata
            |--------------------------------------------------------------------------
            */

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index([
                'organization_id',
                'is_active'
            ]);

            $table->index([
                'is_active',
                'last_generated_at'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_series');
    }
};
