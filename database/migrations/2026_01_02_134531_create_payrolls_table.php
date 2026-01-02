<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('payroll_no');
            $table->integer('period_month');
            $table->integer('period_year');
            $table->decimal('base_salary', 12, 2);
            $table->json('allowances')->nullable();
            $table->json('deductions')->nullable();
            $table->decimal('net_salary', 12, 2);
            $table->string('status')->default('pending');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['merchant_id', 'user_id', 'period_month', 'period_year'], 'unique_payroll_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
