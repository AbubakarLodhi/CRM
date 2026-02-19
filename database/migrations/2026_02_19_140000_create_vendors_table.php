<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->unique();
            $table->string('occupation', 255)->nullable();
            $table->string('reference')->nullable();
            $table->uuid('country_id');
            $table->uuid('city_id');
            $table->string('postal_code')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();

            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('country_id')->references('id')->on('countries');
            $table->foreign('city_id')->references('id')->on('cities');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
