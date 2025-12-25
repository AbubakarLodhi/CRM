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
        Schema::create('merchant_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('logo_path')->nullable();
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('currency')->default('USD');
            $table->string('timezone')->default('UTC');
            $table->timestamps();
            $table->foreignUuid('merchant_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_settings');
    }
};
