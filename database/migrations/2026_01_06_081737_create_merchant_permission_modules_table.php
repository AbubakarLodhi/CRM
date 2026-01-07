<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('merchant_permission_modules', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('merchant_id');
            $table->uuid('permission_module_id');

            $table->timestamps();

            // Prevent duplicate assignments
            $table->unique(['merchant_id', 'permission_module_id']);

            // Foreign keys
            $table->foreign('merchant_id')
                ->references('id')
                ->on('merchants')
                ->cascadeOnDelete();

            $table->foreign('permission_module_id')
                ->references('id')
                ->on('permission_modules')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_permission_modules');
    }
};
