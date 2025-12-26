<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->enum('type', [
                'stock',
                'service',
                'measured_stock',
                'custom'
            ])->default('stock')->after('selling_price');

            $table->enum('unit', [
                'pieces',
                'liter',
                'gram',
                'kg',
                'job',
                'hour',
                'day',
                'sqm',
                'set'
            ])->default('pieces')->after('type');

            $table->boolean('track_inventory')
                ->default(true)
                ->after('unit');

            $table->boolean('is_variable_price')
                ->default(false)
                ->after('track_inventory');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_price', 12, 2)->nullable()->change();
            $table->decimal('selling_price', 12, 2)->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'varient_id')) {
                $table->dropForeign(['varient_id']);
                $table->dropColumn('varient_id');
            }

            if (Schema::hasColumn('products', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->uuid('category_id')->nullable()->change();
            $table->uuid('sub_category_id')->nullable()->change();
            $table->uuid('brand_id')->nullable()->change();
            $table->uuid('brand_model_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

            // restore removed columns (structure only)
            $table->uuid('varient_id')->nullable();
            $table->integer('quantity')->default(0);

            // revert nullable fields
            $table->uuid('category_id')->nullable(false)->change();
            $table->uuid('sub_category_id')->nullable(false)->change();
            $table->uuid('brand_id')->nullable(false)->change();
            $table->uuid('brand_model_id')->nullable(false)->change();

            // revert pricing
            $table->decimal('purchase_price', 12, 2)->nullable(false)->change();
            $table->decimal('selling_price', 12, 2)->nullable(false)->change();

            // remove added columns
            $table->dropColumn([
                'type',
                'unit',
                'track_inventory',
                'is_variable_price'
            ]);
        });
    }
};
