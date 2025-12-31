<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('merchant_settings', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'primary_color',
                'secondary_color',
                'currency',
                'timezone',
            ]);

            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();
            $table->string('warning_color', 20)->nullable();
            $table->string('danger_color', 20)->nullable();
            $table->string('success_color', 20)->nullable();
            $table->string('default_color', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_settings', function (Blueprint $table) {
            $table->dropColumn([
                'primary_color',
                'secondary_color',
                'warning_color',
                'danger_color',
                'success_color',
                'default_color'
            ]);
        });
    }
};
