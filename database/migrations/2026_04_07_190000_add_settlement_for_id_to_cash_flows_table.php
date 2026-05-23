<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_flows', function (Blueprint $table): void {
            $table->uuid('settlement_for_id')->nullable()->after('party_id');
            $table->foreign('settlement_for_id')
                ->references('id')
                ->on('cash_flows')
                ->nullOnDelete();

            $table->index(['settlement_for_id', 'flow_date']);
        });
    }

    public function down(): void
    {
        Schema::table('cash_flows', function (Blueprint $table): void {
            $table->dropForeign(['settlement_for_id']);
            $table->dropIndex(['settlement_for_id', 'flow_date']);
            $table->dropColumn('settlement_for_id');
        });
    }
};

