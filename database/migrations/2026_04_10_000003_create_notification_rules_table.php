<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();

            // =========================
            // MULTI-TENANT
            // =========================
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // =========================
            // EVENTO
            // =========================
            $table->string('type');
            // appointment_created, contact_form, etc

            // =========================
            // CANAL
            // =========================
            $table->string('channel');
            // email | sms | whatsapp

            // =========================
            // DESTINATARIOS
            // =========================
            $table->string('recipient_type');
            // client | admin | custom

            $table->json('custom_recipients')->nullable();
            // ["correo1@gmail.com", "correo2@gmail.com"]

            // =========================
            // CONFIGURACIÓN DE ENVÍO
            // =========================
            $table->boolean('is_enabled')->default(true);

            $table->integer('delay_minutes')->default(0);
            // para recordatorios

            // =========================
            // TEMPLATE
            // =========================
            $table->unsignedBigInteger('template_id')->nullable();

            // =========================
            // CONTROL
            // =========================
            $table->unsignedInteger('max_per_day')->nullable();
            $table->unsignedInteger('max_per_month')->nullable();

            $table->timestamps();


            // =========================
            // UNIQUE
            // =========================
            $table->unique([
                'organization_id',
                'type',
                'channel',
                'recipient_type'
            ], 'nr_org_type_channel_recipient_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
