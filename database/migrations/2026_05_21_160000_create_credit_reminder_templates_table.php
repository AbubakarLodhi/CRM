<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_reminder_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->string('name');
            $table->foreignUuid('notification_template_id')
                ->constrained('notification_templates')
                ->cascadeOnDelete();
            $table->string('schedule_type', 32);
            $table->string('offset_type', 16)->nullable();
            $table->unsignedInteger('offset_value')->nullable();
            $table->string('repeat_type', 16)->nullable();
            $table->unsignedInteger('repeat_value')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['merchant_id', 'is_enabled']);
        });

        Schema::table('credit_reminders', function (Blueprint $table) {
            $table->foreignUuid('credit_reminder_template_id')
                ->nullable()
                ->after('sale_id')
                ->constrained('credit_reminder_templates')
                ->cascadeOnDelete();

            $table->unique(['sale_id', 'credit_reminder_template_id'], 'credit_reminders_sale_template_unique');
        });
    }

    public function down(): void
    {
        Schema::table('credit_reminders', function (Blueprint $table) {
            $table->dropUnique('credit_reminders_sale_template_unique');
            $table->dropForeign(['credit_reminder_template_id']);
            $table->dropColumn('credit_reminder_template_id');
        });

        Schema::dropIfExists('credit_reminder_templates');
    }
};
