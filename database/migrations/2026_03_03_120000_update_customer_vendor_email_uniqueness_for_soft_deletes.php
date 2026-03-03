<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['customers', 'vendors'] as $table) {
            if (! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->softDeletes();
                });
            }
        }

        DB::statement('ALTER TABLE customers DROP CONSTRAINT IF EXISTS customers_email_unique');
        DB::statement('ALTER TABLE vendors DROP CONSTRAINT IF EXISTS vendors_email_unique');

        DB::statement('DROP INDEX IF EXISTS customers_active_email_unique');
        DB::statement('DROP INDEX IF EXISTS vendors_active_email_unique');

        DB::statement('CREATE UNIQUE INDEX customers_active_email_unique ON customers (LOWER(email)) WHERE deleted_at IS NULL AND email IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX vendors_active_email_unique ON vendors (LOWER(email)) WHERE deleted_at IS NULL AND email IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS customers_active_email_unique');
        DB::statement('DROP INDEX IF EXISTS vendors_active_email_unique');

        DB::statement('ALTER TABLE customers ADD CONSTRAINT customers_email_unique UNIQUE (email)');
        DB::statement('ALTER TABLE vendors ADD CONSTRAINT vendors_email_unique UNIQUE (email)');
    }
};
