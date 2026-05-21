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
        Schema::create('client_files', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Multi-tenant
            |--------------------------------------------------------------------------
            */
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Relaciones principales
            |--------------------------------------------------------------------------
            */
            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            // opcional:
            // archivo relacionado a una cita
            /*$table->foreignId('appointment_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();*/

            /*
            |--------------------------------------------------------------------------
            | Auditoría
            |--------------------------------------------------------------------------
            */
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Archivo
            |--------------------------------------------------------------------------
            */

            // nombre visible
            $table->string('name');

            // nombre original del archivo
            $table->string('original_name')->nullable();

            // ruta storage
            $table->string('path');

            // disco laravel:
            // local, s3, r2, spaces, etc
            $table->string('disk')->default('public');

            // mime type
            $table->string('mime_type')->nullable();

            // extensión
            $table->string('extension', 20)->nullable();

            // tamaño bytes
            $table->unsignedBigInteger('size')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Clasificación
            |--------------------------------------------------------------------------
            */

            // image, pdf, consent, study, prescription, etc
            $table->string('category', 50)->nullable();

            // público o privado
            $table->boolean('is_private')->default(true);

            // visible en portal cliente
            $table->boolean('visible_to_client')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Metadata
            |--------------------------------------------------------------------------
            */

            $table->json('metadata')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */
            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index([
                'organization_id',
                'client_id'
            ]);

            $table->index([
                'client_id',
                'category'
            ]);

            /*$table->index([
                'appointment_id'
            ]);*/

            $table->index([
                'uploaded_by'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_files');
    }
};
