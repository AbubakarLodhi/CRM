<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('adds_on', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->timestamps();
            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('brand_model_id')->constrained('brand_models')->cascadeOnDelete()->cascadeOnUpdate();

            $table->unique(['merchant_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adds_on');
    }
};
