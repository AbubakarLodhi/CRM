<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'country_id')) {
                $table->dropColumn('country_id');
            }
            if (Schema::hasColumn('branches', 'city_id')) {
                $table->dropColumn('city_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->uuid('country_id')->nullable();
            $table->uuid('city_id')->nullable();
        });
    }
};
