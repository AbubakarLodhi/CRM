<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Quantity (required)
            $table->integer('quantity')->default(0)->after('selling_price');

            // Required foreign keys
            $table->foreignUuid('category_id')
                ->constrained('categories')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('sub_category_id')
                ->constrained('categories')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('brand_id')
                ->constrained('brands')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('brand_model_id')
                ->constrained('brand_models')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('varient_id')
                ->constrained('varients')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Optional add-ons
            $table->foreignUuid('addon_id')
                ->nullable()
                ->constrained('add_ons')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop only what ACTUALLY exists
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_category_id_foreign');
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_sub_category_id_foreign');
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_brand_id_foreign');
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_brand_model_id_foreign');
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_varient_id_foreign');
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_addon_id_foreign');

            // Drop columns safely
            $table->dropColumn([
                'quantity',
                'category_id',
                'sub_category_id',
                'brand_id',
                'brand_model_id',
                'varient_id',
                'addon_id',
            ]);
        });
    }
};
