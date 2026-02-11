<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();

            // Identidad
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 30)->default('client'); // root | client
            $table->boolean('is_internal')->default(false);

            // Dueño
            $table->foreignId('owner_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Estado
            $table->string('status', 20)->default('active');

            // Contacto
            $table->json('phone')->nullable();
            $table->string('email')->nullable();

            // Branding
            $table->string('theme_key')->nullable();
            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('white_label')->default(false);

            // Dominio
            $table->string('primary_domain')->nullable();
            $table->json('domains')->nullable();
            $table->boolean('force_https')->default(true);

            // Extra
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
