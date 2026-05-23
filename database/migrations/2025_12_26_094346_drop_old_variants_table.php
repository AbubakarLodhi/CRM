<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Remove old typo FK from products
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'varient_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['varient_id']);
                $table->dropColumn('varient_id');
            });
        }

        // Drop typo table
        Schema::dropIfExists('varients');
    }

    public function down(): void
    {
        // Restore typo table ONLY (exact rollback)
        Schema::create('varients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');

            $table->foreignUuid('merchant_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('brand_model_id')
                ->constrained('brand_models')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->unique(['merchant_id', 'name']);
        });

        // Restore column in products
        Schema::table('products', function (Blueprint $table) {
            $table->foreignUuid('varient_id')
                ->nullable()
                ->constrained('varients')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }
};
