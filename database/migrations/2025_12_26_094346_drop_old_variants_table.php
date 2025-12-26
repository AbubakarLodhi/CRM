<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'varient_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['varient_id']);
                $table->dropColumn('varient_id');
            });
        }

        Schema::dropIfExists('varients');
    }

    public function down(): void
    {
        Schema::create('variants', function (Blueprint $table) {
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
    }
};
