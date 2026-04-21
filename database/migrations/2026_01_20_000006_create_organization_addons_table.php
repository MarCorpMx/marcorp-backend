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
        Schema::create('organization_addons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            // Identificador del addon (clave lógica)
            $table->string('key'); // ej: extra_reminders, custom_domain

            // Para addons acumulables
            $table->integer('quantity')->default(1);

            // Control de estado
            $table->string('status')->default('active');
            // active | expired | canceled

            // Control de tiempo (muy importante)
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Datos extra (flexibilidad total)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Índices importantes
            $table->index(['organization_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_addons');
    }
};
