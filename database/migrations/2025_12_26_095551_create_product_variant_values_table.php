<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('product_variant_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('product_option_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('product_option_value_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->unique(['product_variant_id', 'product_option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_values');
    }
};
