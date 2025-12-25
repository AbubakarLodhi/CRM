<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Merchant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('primary_contact_name');
            $table->string('primary_contact_number')->unique();
            $table->string('primary_contact_email')->unique();
            $table->json('social_media_handles')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(false);
            $table->enum('status', Merchant::getStatuses())->default(Merchant::STATUS_PENDING);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
