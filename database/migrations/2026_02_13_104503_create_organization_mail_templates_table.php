<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_mail_templates', function (Blueprint $table) {
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

            $table->string('name');
            // Nombre visible en panel admin (ej: "Auto respuesta contacto")

            $table->string('subject');

            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();

            // Permite activar/desactivar la plantilla
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Evita duplicados del mismo tipo por organización
            $table->unique(['organization_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_mail_templates');
    }
};
