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
            $table->dropColumn([
                'logo_path',
                'primary_color',
                'secondary_color',
                'currency',
                'timezone',
            ]);

            /* ================= LIGHT MODE COLORS ================= */
            $table->string('primary_color_light', 20)->nullable();
            $table->string('secondary_color_light', 20)->nullable();
            $table->string('warning_color_light', 20)->nullable();
            $table->string('danger_color_light', 20)->nullable();
            $table->string('success_color_light', 20)->nullable();

            /* ================= DARK MODE COLORS ================= */
            $table->string('primary_color_dark', 20)->nullable();
            $table->string('secondary_color_dark', 20)->nullable();
            $table->string('warning_color_dark', 20)->nullable();
            $table->string('danger_color_dark', 20)->nullable();
            $table->string('success_color_dark', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_settings', function (Blueprint $table) {
            $table->dropColumn([
                'primary_color_light',
                'secondary_color_light',
                'warning_color_light',
                'danger_color_light',
                'success_color_light',

                'primary_color_dark',
                'secondary_color_dark',
                'warning_color_dark',
                'danger_color_dark',
                'success_color_dark',
            ]);
        });
    }
};
