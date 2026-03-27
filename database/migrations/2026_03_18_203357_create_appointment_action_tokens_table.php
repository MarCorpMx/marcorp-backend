<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_action_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appointment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('token')->unique();

            // Escalable (controlará todas las acciones)
            $table->string('action');

            // Control de vida
            $table->timestamp('expires_at');

            // Estado del token
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            // Auditoría
            $table->string('used_ip')->nullable();
            $table->text('used_user_agent')->nullable();

            $table->timestamps();

            // Índices
            $table->index('appointment_id');
            $table->index('expires_at');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_action_tokens');
    }
};
