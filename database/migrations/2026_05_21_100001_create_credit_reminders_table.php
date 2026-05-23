<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->unique()->constrained('sales')->cascadeOnDelete();
            $table->string('reminder_type', 16);
            $table->unsignedInteger('first_reminder_value')->default(0);
            $table->string('repeat_type', 16)->nullable();
            $table->unsignedInteger('repeat_value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_send_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'next_send_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_reminders');
    }
};
