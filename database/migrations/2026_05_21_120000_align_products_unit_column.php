<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNITS = ['pcs', 'liter', 'gram', 'kg', 'job', 'hour', 'day', 'sqm', 'set'];

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if ($this->enumIncludes('pcs') && ! $this->enumIncludes('pieces')) {
            return;
        }

        DB::statement("
            ALTER TABLE products
            MODIFY COLUMN unit ENUM(
                'pieces',
                'pcs',
                'liter',
                'gram',
                'kg',
                'job',
                'hour',
                'day',
                'sqm',
                'set'
            ) NOT NULL DEFAULT 'pcs'
        ");

        DB::table('products')->where('unit', 'pieces')->update(['unit' => 'pcs']);

        DB::statement("
            ALTER TABLE products
            MODIFY COLUMN unit ENUM(
                'pcs',
                'liter',
                'gram',
                'kg',
                'job',
                'hour',
                'day',
                'sqm',
                'set'
            ) NOT NULL DEFAULT 'pcs'
        ");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if ($this->enumIncludes('pieces') && ! $this->enumIncludes('pcs')) {
            return;
        }

        DB::statement("
            ALTER TABLE products
            MODIFY COLUMN unit ENUM(
                'pieces',
                'pcs',
                'liter',
                'gram',
                'kg',
                'job',
                'hour',
                'day',
                'sqm',
                'set'
            ) NOT NULL DEFAULT 'pieces'
        ");

        DB::table('products')->where('unit', 'pcs')->update(['unit' => 'pieces']);

        DB::statement("
            ALTER TABLE products
            MODIFY COLUMN unit ENUM(
                'pieces',
                'liter',
                'gram',
                'kg',
                'job',
                'hour',
                'day',
                'sqm',
                'set'
            ) NOT NULL DEFAULT 'pieces'
        ");
    }

    private function enumIncludes(string $value): bool
    {
        $row = DB::selectOne("SHOW COLUMNS FROM products WHERE Field = 'unit'");

        if (! $row || ! isset($row->Type)) {
            return false;
        }

        return str_contains((string) $row->Type, "'{$value}'");
    }
};
