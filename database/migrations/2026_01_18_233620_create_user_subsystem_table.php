<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_subsystem', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('subsystem_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('membership_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('role', ['root', 'admin', 'manager', 'user'])
                ->default('admin');

            $table->boolean('active')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'subsystem_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subsystem');
    }
};
