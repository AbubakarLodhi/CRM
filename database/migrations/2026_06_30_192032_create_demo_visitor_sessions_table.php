<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_visitor_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('visitor_hash', 64)->unique();
            $table->string('ip_address', 45);
            $table->dateTime('started_at');
            $table->dateTime('expires_at');
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('expires_at');
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_visitor_sessions');
    }
};
