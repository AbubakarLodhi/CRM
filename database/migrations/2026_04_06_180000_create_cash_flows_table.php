<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_flows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->uuidMorphs('party');
            $table->enum('flow_type', ['advance', 'loan']);
            $table->enum('direction', ['in', 'out']);
            $table->decimal('amount', 18, 2);
            $table->date('flow_date');
            $table->string('method')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['merchant_id', 'flow_date']);
            $table->index(['party_type', 'party_id', 'flow_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_flows');
    }
};

