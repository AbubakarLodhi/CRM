<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_dynamic_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('section', 20)->index(); // header | footer
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['merchant_id', 'section', 'name'], 'invoice_dynamic_groups_unique_group');
        });

        Schema::create('invoice_dynamic_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_dynamic_group_id')
                ->constrained('invoice_dynamic_groups')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('label');
            $table->string('value_type', 30)->default('static')->index();
            $table->string('value_key')->nullable();
            $table->string('static_value')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_dynamic_fields');
        Schema::dropIfExists('invoice_dynamic_groups');
    }
};
