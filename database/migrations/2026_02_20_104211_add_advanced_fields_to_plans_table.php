<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {

            // Control visual
            $table->boolean('is_visible')
                ->default(true)
                ->after('is_active');

            $table->boolean('is_featured')
                ->default(false)
                ->after('is_visible');

            // Control de limitación (Founder)
            $table->boolean('is_limited')
                ->default(false)
                ->after('billing_period');

            $table->integer('max_sales')
                ->nullable()
                ->after('is_limited');

            $table->integer('sales_count')
                ->default(0)
                ->after('max_sales');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'is_visible',
                'is_featured',
                'is_limited',
                'max_sales',
                'sales_count',
            ]);
        });
    }
};