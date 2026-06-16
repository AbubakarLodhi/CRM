<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('asset_type_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('asset_code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('model_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 14, 2)->nullable();
            $table->decimal('current_value', 14, 2)->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->string('status')->default('active');
            $table->string('condition')->default('good');
            $table->string('location')->nullable();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['merchant_id', 'asset_code']);
            $table->index(['merchant_id', 'asset_type_id']);
            $table->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
