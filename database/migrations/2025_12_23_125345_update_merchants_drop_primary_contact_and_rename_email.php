<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            // Drop columns
            $table->dropColumn([
                'primary_contact_name',
                'primary_contact_number',
            ]);

            // Rename column
            $table->renameColumn('primary_contact_email', 'email');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            // Recreate dropped columns
            $table->string('primary_contact_name');
            $table->string('primary_contact_number')->unique();

            // Rename column back
            $table->renameColumn('email', 'primary_contact_email');
        });
    }
};
