<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->unique(['id', 'business_id'], 'branches_id_business_id_unique');
        });

        Schema::create('customer_businesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();

            $table->foreignUuid('customer_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('business_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->unique(['customer_id', 'business_id']);
        });

        Schema::create('customer_branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();

            $table->foreignUuid('customer_id')
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

            $table->unique(['customer_id', 'branch_id']);
            $table->unique(['customer_id', 'business_id', 'branch_id'], 'customer_branches_customer_business_branch_unique');

            $table->foreign(['customer_id', 'business_id'])
                ->references(['customer_id', 'business_id'])
                ->on('customer_businesses')
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
        Schema::dropIfExists('customer_branches');
        Schema::dropIfExists('customer_businesses');

        Schema::table('branches', function (Blueprint $table) {
            $table->dropUnique('branches_id_business_id_unique');
        });
    }
};
