<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organization_subsystems', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('subsystem_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('plan_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->string('status', 20)->default('trial');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->boolean('is_paid')->default(false);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['organization_id', 'subsystem_id']);

            $table->index('organization_id');
            $table->index('subsystem_id');
            $table->index('plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_subsystems');
    }
};
