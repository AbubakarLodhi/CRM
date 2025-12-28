<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration {
    public function up(): void
    {
        // 1. Drop old constraint
        DB::statement('
            ALTER TABLE products
            DROP CONSTRAINT IF EXISTS products_unit_check
        ');

        // 2. Normalize existing data
        DB::statement("
            UPDATE products
            SET unit = 'pcs'
            WHERE unit = 'pieces'
        ");

        // 3. Add new constraint
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
        // rollback constraint
        DB::statement('
            ALTER TABLE products
            DROP CONSTRAINT IF EXISTS products_unit_check
        ');

        // rollback data
        DB::statement("
            UPDATE products
            SET unit = 'pieces'
            WHERE unit = 'pcs'
        ");

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
