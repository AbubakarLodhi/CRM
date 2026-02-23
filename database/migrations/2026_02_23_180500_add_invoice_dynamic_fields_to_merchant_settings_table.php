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
        Schema::table('merchant_settings', function (Blueprint $table) {
            $table->json('invoice_header_groups')->nullable()->after('default_color');
            $table->json('invoice_footer_groups')->nullable()->after('invoice_header_groups');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_settings', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_header_groups',
                'invoice_footer_groups',
            ]);
        });
    }
};
