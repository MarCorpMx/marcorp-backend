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
        // Si el cliente es individual → solo tendrá 1 profesional
        // Si es consultorio → tendrá varios
        Schema::create('professionals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('specialty')->nullable(); // Psicólogo, Coach, Médico general
            // Contacto
            $table->json('phone')->nullable();
            $table->string('email')->nullable();
            
            $table->string('color')->nullable(); // Para mostrarlo en calendario

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professionals');
    }
};
