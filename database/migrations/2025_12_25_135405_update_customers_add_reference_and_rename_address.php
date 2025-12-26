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

            $table->renameColumn('address', 'city');

            $table->foreignUuid('reference_id')->nullable()->after('merchant_id')
                ->constrained('customers')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {

            $table->dropForeign(['reference_id']);
            $table->dropColumn('reference_id');


            $table->renameColumn('city', 'address');
        });
    }
};
