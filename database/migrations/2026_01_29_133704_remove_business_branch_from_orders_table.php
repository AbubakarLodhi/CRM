<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            // 🔥 Drop FKs first (Postgres-safe)
            if (Schema::hasColumn('orders', 'business_id')) {
                $table->dropForeign(['business_id']);
                $table->dropColumn('business_id');
            }

            if (Schema::hasColumn('orders', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            // ⏪ Restore only if rollback is needed
            $table->uuid('business_id')->nullable()->after('merchant_id');
            $table->uuid('branch_id')->nullable()->after('business_id');

            $table->foreign('business_id')->references('id')->on('businesses')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }
};
