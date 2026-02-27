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
        Schema::create('agenda_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('professional_id')
                ->constrained()
                ->cascadeOnDelete();

            // Duración por defecto de cita (minutos)
            $table->integer('appointment_duration')->default(60);

            // Tiempo de descanso entre citas
            $table->integer('break_between_appointments')->default(0);

            // Permitir reservas online
            $table->boolean('allow_online_booking')->default(true);

            // Anticipación mínima para reservar (horas)
            $table->integer('minimum_notice_hours')->default(2);

            // Permitir cancelar
            $table->boolean('allow_cancellation')->default(true);

            // Horas mínimas para cancelar
            $table->integer('cancellation_limit_hours')->default(12);

            // Zona horaria
            $table->string('timezone')->default('America/Mexico_City');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_settings');
    }
};
