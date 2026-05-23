<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('purchases', 'payment_type')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->string('payment_type')->nullable()->after('vendor_id');
            });
        }

        DB::table('purchases')
            ->whereNull('payment_type')
            ->update(['payment_type' => 'cash']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchases', 'payment_type')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('payment_type');
            });
        }
    }
};

