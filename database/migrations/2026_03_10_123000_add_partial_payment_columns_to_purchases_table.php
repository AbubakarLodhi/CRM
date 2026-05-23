<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'paid_amount')) {
                $table->decimal('paid_amount', 18, 2)->default(0)->after('total_amount');
            }

            if (! Schema::hasColumn('purchases', 'due_amount')) {
                $table->decimal('due_amount', 18, 2)->default(0)->after('paid_amount');
            }
        });

        DB::table('purchases')->update([
            'paid_amount' => DB::raw("
                CASE
                    WHEN LOWER(COALESCE(payment_type, 'credit')) = 'cash' THEN COALESCE(total_amount, 0)
                    ELSE 0
                END
            "),
            'due_amount' => DB::raw("
                CASE
                    WHEN LOWER(COALESCE(payment_type, 'credit')) = 'cash' THEN 0
                    ELSE COALESCE(total_amount, 0)
                END
            "),
        ]);
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'due_amount')) {
                $table->dropColumn('due_amount');
            }

            if (Schema::hasColumn('purchases', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
        });
    }
};
