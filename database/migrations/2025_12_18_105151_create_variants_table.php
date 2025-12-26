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
        DB::statement(
            'ALTER TABLE IF EXISTS varients
             DROP CONSTRAINT IF EXISTS variants_merchant_id_name_unique'
        );

        Schema::create('variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();

            $table->foreignUuid('merchant_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('brand_model_id')
                ->constrained('brand_models')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->unique(['merchant_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variants');

        // Also drop the index on rollback (extra safety)
        DB::statement(
            'ALTER TABLE IF EXISTS variants
             DROP CONSTRAINT IF EXISTS variants_merchant_id_name_unique'
        );
    }
};
