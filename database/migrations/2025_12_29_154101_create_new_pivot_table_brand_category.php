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
        Schema::create('brand_category', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('merchant_id');
            $table->uuid('brand_id');
            $table->uuid('category_id');

            $table->timestamps();

            // Indexes
            $table->index(['merchant_id']);
            $table->index(['brand_id']);
            $table->index(['category_id']);

            // Prevent duplicates
            $table->unique(
                ['merchant_id', 'brand_id', 'category_id'],
                'brand_category_unique'
            );

            // Optional FKs (enable if your DB is consistent)
            $table->foreign('merchant_id')->references('id')->on('merchants')->cascadeOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_category');
    }
};
