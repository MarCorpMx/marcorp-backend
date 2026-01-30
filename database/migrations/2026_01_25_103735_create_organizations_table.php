<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->foreignId('owner_user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('status', 20)->default('active');

            $table->json('phone', 50)->nullable();
            $table->string('email')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            // $table->softDeletes(); // opcional
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
