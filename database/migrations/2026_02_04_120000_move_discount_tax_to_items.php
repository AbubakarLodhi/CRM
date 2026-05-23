<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->default(0)->after('line_total');
            $table->decimal('tax', 12, 2)->default(0)->after('discount');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->default(0)->after('line_total');
            $table->decimal('tax', 12, 2)->default(0)->after('discount');
        });


        if (Schema::hasColumn('sales', 'discount')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn(['discount', 'tax']);
            });
        }

        if (Schema::hasColumn('purchases', 'discount')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn(['discount', 'tax']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['discount', 'tax']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['discount', 'tax']);
        });
    }
};
