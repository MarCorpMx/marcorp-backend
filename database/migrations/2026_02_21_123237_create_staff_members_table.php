<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Identidad
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('name'); // para display rápido (cacheado)
            $table->string('slug')->nullable(); // para booking público (diana-islas)

            // Contacto
            $table->string('email')->nullable();
            $table->json('phone')->nullable(); // ngx-intl-tel-input

            // Perfil Profesional
            $table->string('title')->nullable(); // Ej: "Tanatólogo", "Psicóloga"
            $table->string('specialty')->nullable(); // Ej: "Tanatología"
            $table->text('bio')->nullable(); // descripción pública

            // Media
            $table->string('avatar')->nullable(); // URL imagen perfil

            // Configuración
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true); // visible en booking
            $table->boolean('accepts_online')->default(true);
            $table->boolean('accepts_presential')->default(true);

            // Metadata flexible (para futuro)
            $table->json('settings')->nullable();

            // Timestamps
            $table->timestamps();

            // Índices
            $table->index('organization_id');
            $table->index('user_id');
            $table->index('is_active');
            $table->index('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
