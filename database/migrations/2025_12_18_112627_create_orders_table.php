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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('status');
            $table->text('status_notes')->nullable();
            $table->timestamps();
            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('sale_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
