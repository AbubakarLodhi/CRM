<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop old unit check constraint
        DB::statement('
            ALTER TABLE products
            DROP CONSTRAINT IF EXISTS products_unit_check
        ');

        // 2. Add corrected constraint with "pcs"
        DB::statement("
            ALTER TABLE products
            ADD CONSTRAINT products_unit_check
            CHECK (
                unit IN (
                    'pcs',
                    'liter',
                    'gram',
                    'kg',
                    'job',
                    'hour',
                    'day',
                    'sqm',
                    'set'
                )
            )
        ");
    }

    public function down(): void
    {
        // rollback to old constraint (optional)
        DB::statement('
            ALTER TABLE products
            DROP CONSTRAINT IF EXISTS products_unit_check
        ');

        DB::statement("
            ALTER TABLE products
            ADD CONSTRAINT products_unit_check
            CHECK (
                unit IN (
                    'pieces',
                    'liter',
                    'gram',
                    'kg',
                    'job',
                    'hour',
                    'day',
                    'sqm',
                    'set'
                )
            )
        ");
    }
};
