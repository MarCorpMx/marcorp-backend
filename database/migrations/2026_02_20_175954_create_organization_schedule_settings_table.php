<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_schedule_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained()->onDelete('cascade');

            // Descanso entre citas
            $table->integer('break_between_appointments')->default(0);

            // Reglas internas visibles en agenda
            $table->json('rules')->nullable();

            // Horarios laborales
            $table->json('working_hours')->nullable();

            // Días no laborables
            $table->json('holidays')->nullable();

            // Zona horaria
            $table->string('timezone')->default('UTC');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_schedule_settings');
    }
};
