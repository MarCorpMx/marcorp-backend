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

            // Autor (usuario autenticado)
            $table->unsignedBigInteger('author_id')->nullable();
            $table->foreign('author_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->string('title')->nullable();
            $table->longText('content');

            $table->boolean('is_private')->default(false);

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
