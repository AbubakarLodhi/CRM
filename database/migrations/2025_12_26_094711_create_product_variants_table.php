<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Ownership
            $table->foreignUuid('merchant_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Parent product
            $table->foreignUuid('product_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Variant identity
            $table->string('name')->nullable();      // e.g. "72V / 30Ah"
            $table->string('sku')->nullable();

            // Optional pricing override
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->decimal('selling_price', 12, 2)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['merchant_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
