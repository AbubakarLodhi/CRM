<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('sale_return_id');
            $table->uuid('sale_item_id');

            $table->uuid('business_id');
            $table->uuid('branch_id');
            $table->uuid('product_id');

            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);

            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('tax', 5, 2)->default(0);

            $table->timestamps();

            $table->foreign('sale_return_id')
                ->references('id')
                ->on('sale_returns')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};

