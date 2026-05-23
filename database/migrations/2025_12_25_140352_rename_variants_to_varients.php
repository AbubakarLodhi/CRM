<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('variants') && !Schema::hasTable('varients')) {
            DB::statement('ALTER TABLE variants RENAME TO varients');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('varients') && !Schema::hasTable('variants')) {
            DB::statement('ALTER TABLE varients RENAME TO variants');
        }
    }
};
