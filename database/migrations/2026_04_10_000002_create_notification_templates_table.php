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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();

            // La columna nullable para permitir templates globales
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();

            // Tipo de plantilla (clave interna del sistema)
            $table->string('type');
            // Ej: contact_auto_reply, appointment_confirmed, appointment_reminder

            // para saber si es de una organizacion o general
            $table->string('layout_type')->nullable(); 

            $table->string('channel');
            // email | sms | whatsapp

            $table->string('name');
            // Nombre visible en panel admin (ej: "Auto respuesta contacto")

            $table->string('subject')->nullable(); // email

            $table->longText('body')->nullable(); // html o texto
            $table->longText('body_text')->nullable(); // fallback

            // Permite activar/desactivar la plantilla
            $table->boolean('is_active')->default(true);

            $table->json('variables')->nullable();
            // {{name}}, {{date}}, etc

            $table->timestamps();

            // Evita duplicados del mismo tipo por organización
            $table->unique(['organization_id', 'type', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
