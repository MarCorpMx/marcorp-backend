<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_notes', function (Blueprint $table) {

            $table->id();

            $table->foreignId('appointment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->text('note');

            $table->string('type');
            /*$table->enum('type', [
                'admin_note',
                'status_change',
                'cancellation',
                'client_cancellation',
                'no_show',
                'reschedule',
                'client_reschedule'
            ])->default('admin_note');*/

            $table->timestamps();

            $table->index('appointment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_notes');
    }
};
