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
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('whatsapp_number', 50)->nullable()->after('website');
            $table->string('ntn_number', 50)->nullable()->after('whatsapp_number');
            $table->json('extra_fields')->nullable()->after('ntn_number');
            $table->decimal('cash_in_hand', 18, 2)->nullable()->after('website');
            $table->decimal('cash_in_bank', 18, 2)->nullable()->after('cash_in_hand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_number',
                'ntn_number',
                'extra_fields',
                'cash_in_hand',
                'cash_in_bank',
            ]);
        });
    }
};
