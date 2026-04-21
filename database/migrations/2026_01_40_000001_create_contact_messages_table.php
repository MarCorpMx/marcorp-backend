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
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            // Multi-tenant real
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            // Datos del remitente
            $table->string('first_name', 250);
            $table->string('last_name', 100)->nullable();
            $table->string('email', 150);

            $table->string('subject', 150)->nullable();

            $table->json('phone')->nullable();
            $table->json('services')->nullable();

            $table->text('message');

            $table->enum('status', ['new', 'read', 'replied', 'archived'])
                ->default('new');

            // Auditoría ligera
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
