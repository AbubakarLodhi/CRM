<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['merchant_id']);

            // Make column nullable
            $table->uuid('merchant_id')->nullable()->change();

            // Re-add foreign key with nullOnDelete
            $table->foreign('merchant_id')
                ->references('id')
                ->on('merchants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropForeign(['merchant_id']);

            $table->uuid('merchant_id')->nullable(false)->change();

            $table->foreign('merchant_id')
                ->references('id')
                ->on('merchants')
                ->cascadeOnDelete();
        });
    }
};
