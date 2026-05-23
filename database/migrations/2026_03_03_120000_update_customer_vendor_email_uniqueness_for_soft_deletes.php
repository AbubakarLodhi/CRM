<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['customers', 'vendors'] as $table) {
            if (!Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->softDeletes();
                });
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                try {
                    $blueprint->dropUnique("{$table}_email_unique");
                } catch (\Exception $e) {
                    // Index may not exist
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['customers', 'vendors'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unique('email');
            });
        }
    }
};