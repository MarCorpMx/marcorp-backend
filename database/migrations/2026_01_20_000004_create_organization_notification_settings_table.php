<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_notification_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            // Destinatarios
            $table->json('notification_to')->nullable();
            $table->json('notification_cc')->nullable();
            $table->json('notification_bcc')->nullable();

            // Funcionalidades
            $table->boolean('auto_reply_enabled')->default(true);
            $table->boolean('emergency_footer_enabled')->default(false);

            // Horario laboral
            $table->json('office_hours')->nullable();

            $table->timestamps();

            // Cada organización solo debe tener una config activa
            $table->unique('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_notification_settings');
    }
};
