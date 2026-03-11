<?php

use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->uuidMorphs('paymentable');
            $table->nullableUuidMorphs('party');
            $table->enum('direction', ['in', 'out']);
            $table->enum('entry_type', ['payment', 'refund', 'adjustment'])->default('payment');
            $table->decimal('amount', 18, 2);
            $table->date('payment_date');
            $table->string('method')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['merchant_id', 'payment_date']);
            $table->index(['paymentable_type', 'paymentable_id', 'deleted_at']);
            $table->index(['party_type', 'party_id', 'deleted_at']);
        });

        $now = now();

        Sale::query()
            ->where('paid_amount', '>', 0)
            ->select(['id', 'merchant_id', 'customer_id', 'sale_date', 'created_by', 'paid_amount', 'created_at'])
            ->chunk(500, function ($sales) use ($now): void {
                $rows = [];

                foreach ($sales as $sale) {
                    $rows[] = [
                        'id' => (string) Str::uuid(),
                        'merchant_id' => $sale->merchant_id,
                        'paymentable_type' => Sale::class,
                        'paymentable_id' => $sale->id,
                        'party_type' => \App\Models\Customer::class,
                        'party_id' => $sale->customer_id,
                        'direction' => 'in',
                        'entry_type' => 'payment',
                        'amount' => (float) $sale->paid_amount,
                        'payment_date' => ($sale->sale_date ?? $sale->created_at ?? $now)->toDateString(),
                        'created_by' => $sale->created_by,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('payments')->insert($rows);
                }
            });

        Purchase::query()
            ->where('paid_amount', '>', 0)
            ->select(['id', 'merchant_id', 'vendor_id', 'purchase_date', 'created_by', 'paid_amount', 'created_at'])
            ->chunk(500, function ($purchases) use ($now): void {
                $rows = [];

                foreach ($purchases as $purchase) {
                    $rows[] = [
                        'id' => (string) Str::uuid(),
                        'merchant_id' => $purchase->merchant_id,
                        'paymentable_type' => Purchase::class,
                        'paymentable_id' => $purchase->id,
                        'party_type' => \App\Models\Vendor::class,
                        'party_id' => $purchase->vendor_id,
                        'direction' => 'out',
                        'entry_type' => 'payment',
                        'amount' => (float) $purchase->paid_amount,
                        'payment_date' => ($purchase->purchase_date ?? $purchase->created_at ?? $now)->toDateString(),
                        'created_by' => $purchase->created_by,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('payments')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
