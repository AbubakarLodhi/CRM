<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->unique(['merchant_id', 'parent_id', 'name']);
        });

        // 🔥 ADD SELF FK AFTER TABLE IS CREATED
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
