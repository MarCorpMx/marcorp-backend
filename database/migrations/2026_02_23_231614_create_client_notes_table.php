<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_notes', function (Blueprint $table) {
            $table->id();

            // Multi-tenant isolation
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            // Cliente
            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            // Citas
            $table->foreignId('appointment_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Autor (usuario autenticado)
            $table->unsignedBigInteger('author_id')->nullable();
            $table->foreign('author_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Contexto    
            $table->string('title')->nullable();
            $table->longText('content');

            // Tipo
            $table->string('type')->default('general');
            //general, clinical, psychological, allergy, warning, follow_up, internal

            // Privacidad / importancia
            $table->boolean('is_private')->default(false);
            $table->string('visibility')->default('team');
            // private, team, public
            $table->boolean('is_important')->default(false); // Notas críticas

            // Metada flexible
            $table->json('meta')->nullable();
            /*{
                "mood": "anxious",
                "pain_level": 7,
                "skin_reaction": "mild redness"
            }*/
             
            // Adjuntos
            $table->json('attachments')->nullable();
            /*[
                "url1.jpg",
                "pdf2.pdf"
                ]*/    

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Índices importantes (performance SaaS)
            |--------------------------------------------------------------------------
            */
            $table->index(['organization_id', 'client_id']);
            $table->index(['organization_id', 'created_at']);
            $table->index(['client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_notes');
    }
};


//php artisan migrate:refresh --path=database/migrations/2026_02_23_231614_create_client_notes_table.php