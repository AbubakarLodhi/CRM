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
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['reference_id']);
            $table->dropColumn('reference_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('merchant_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('reference');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->uuid('reference_id')->nullable();
            $table->foreign('reference_id')
                ->references('id')
                ->on('customers')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

    }
};
