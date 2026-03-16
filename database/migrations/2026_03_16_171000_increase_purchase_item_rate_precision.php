<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE purchase_items ALTER COLUMN discount TYPE numeric(12,6)');
        DB::statement('ALTER TABLE purchase_items ALTER COLUMN tax TYPE numeric(12,6)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE purchase_items ALTER COLUMN discount TYPE numeric(12,2)');
        DB::statement('ALTER TABLE purchase_items ALTER COLUMN tax TYPE numeric(12,2)');
    }
};
