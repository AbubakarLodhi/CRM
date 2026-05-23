<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_return_item_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('sale_return_item_id');
            $table->uuid('product_variant_id');

            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);

            $table->timestamps();

            $table->foreign('sale_return_item_id')
                ->references('id')
                ->on('sale_return_items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_item_variants');
    }
};
