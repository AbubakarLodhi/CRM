<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'sale_returns',
            'sale_return_items',
            'sale_return_item_variants',
            'purchase_returns',
            'purchase_return_items',
            'purchase_return_item_variants',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'sale_returns',
            'sale_return_items',
            'sale_return_item_variants',
            'purchase_returns',
            'purchase_return_items',
            'purchase_return_item_variants',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
