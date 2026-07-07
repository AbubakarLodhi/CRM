<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_visitor_sessions', function (Blueprint $table) {
            $table->foreignUuid('merchant_id')
                ->nullable()
                ->after('visitor_hash')
                ->constrained('merchants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('demo_visitor_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merchant_id');
        });
    }
};
