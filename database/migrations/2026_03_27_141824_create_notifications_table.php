<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // =========================
            // MULTI-TENANT
            // =========================
            $table->unsignedBigInteger('organization_id');

            // =========================
            // RELACIONES OPCIONALES
            // =========================
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            // =========================
            // CONTEXTO
            // =========================
            $table->string('type');
            // appointment_created, contact_form, etc

            $table->string('event_key')->nullable();
            // Ej: appointment_123_created

            // =========================
            // CANAL
            // =========================
            $table->string('channel');
            // email | sms | whatsapp

            // =========================
            // DESTINATARIO
            // =========================
            $table->string('recipient');
            // correo o teléfono

            $table->string('recipient_name')->nullable();

            // =========================
            // CONTENIDO
            // =========================
            $table->string('subject')->nullable(); // email
            $table->text('message')->nullable(); // mensaje final enviado

            $table->string('template')->nullable();
            // referencia al template usado

            $table->json('payload')->nullable();
            // datos usados para generar el mensaje

            // =========================
            // ESTADO
            // =========================
            $table->string('status')->default('pending');
            // pending | processing | sent | failed

            // =========================
            // INTENTOS
            // =========================
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(3);

            // =========================
            // PRIORIDAD
            // =========================
            $table->unsignedTinyInteger('priority')->default(1);
            // 1 normal | 2 alta

            // =========================
            // PROVIDER
            // =========================
            $table->string('provider')->nullable();
            // sendgrid, twilio

            $table->string('provider_message_id')->nullable();

            // =========================
            // ERRORES
            // =========================
            $table->text('error_message')->nullable();
            $table->timestamp('failed_at')->nullable();

            // =========================
            // TIEMPOS
            // =========================
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            // =========================
            // COSTO (FUTURO)
            // =========================
            $table->decimal('cost', 10, 4)->nullable();

            $table->timestamps();

            // =========================
            // ÍNDICES (IMPORTANTE)
            // =========================
            $table->index('organization_id');
            $table->index(['organization_id', 'type']);
            $table->index('status');
            $table->index('channel');
            $table->index('scheduled_at');
            $table->index('event_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
