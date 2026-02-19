<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_return_item_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('purchase_return_item_id');
            $table->uuid('product_variant_id');

            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);

            $table->timestamps();

            $table->foreign('purchase_return_item_id')
                ->references('id')
                ->on('purchase_return_items')
                ->cascadeOnDelete();

            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->restrictOnDelete();

            $table->index(['purchase_return_item_id']);
            $table->index(['product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_item_variants');
    }
};
