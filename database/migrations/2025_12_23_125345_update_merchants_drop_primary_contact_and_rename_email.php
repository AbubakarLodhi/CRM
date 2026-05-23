<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            if (Schema::hasColumn('merchants', 'primary_contact_name')) {
                $table->dropColumn('primary_contact_name');
            }

            if (Schema::hasColumn('merchants', 'primary_contact_number')) {
                $table->dropColumn('primary_contact_number');
            }

            if (Schema::hasColumn('merchants', 'primary_contact_email')) {
                $table->renameColumn('primary_contact_email', 'email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('primary_contact_name')->nullable();
            $table->string('primary_contact_number')->nullable()->unique();

            $table->renameColumn('email', 'primary_contact_email');
        });
    }
};
