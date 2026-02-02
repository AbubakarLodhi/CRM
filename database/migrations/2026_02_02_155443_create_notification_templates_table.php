<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('merchant_id')->nullable()->index();
            $table->string('event')->index();
            $table->enum('channel', ['email', 'sms', 'whatsapp'])->index();
            $table->string('subject')->nullable();
            $table->longText('content');
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
