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
        Schema::rename('adds_on', 'add_ons');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('add_ons', 'adds_on');
    }
};
