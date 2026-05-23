<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_businesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();

            $table->foreignUuid('vendor_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('business_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->unique(['vendor_id', 'business_id']);
        });

        Schema::create('vendor_branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();

            $table->foreignUuid('vendor_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('business_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('branch_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->unique(['vendor_id', 'branch_id']);
            $table->unique(['vendor_id', 'business_id', 'branch_id'], 'vendor_branches_vendor_business_branch_unique');

            $table->foreign(['vendor_id', 'business_id'])
                ->references(['vendor_id', 'business_id'])
                ->on('vendor_businesses')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign(['branch_id', 'business_id'])
                ->references(['id', 'business_id'])
                ->on('branches')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_branches');
        Schema::dropIfExists('vendor_businesses');
    }
};
