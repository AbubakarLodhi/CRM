<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            $table->foreignUuid('business_id')
                ->nullable()
                ->after('merchant_id')
                ->constrained()
                ->nullOnDelete();

            $table->foreignUuid('branch_id')
                ->nullable()
                ->after('business_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['business_id', 'branch_id', 'flow_date'], 'cash_flows_business_branch_flow_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            $table->dropIndex('cash_flows_business_branch_flow_date_index');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('business_id');
        });
    }
};
