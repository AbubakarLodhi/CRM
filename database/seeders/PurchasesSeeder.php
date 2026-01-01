<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PurchasesSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = Merchant::whereIn('email', [
            'info@zgngreenpvt.com',
            'info@halaynoor.com',
        ])->get();

        if ($merchants->isEmpty()) {
            $this->command->warn('No merchants found. Please run MerchantsSeeder first.');

            return;
        }

        foreach ($merchants as $merchant) {
            $this->createPurchasesForMerchant($merchant);
        }
    }

    private function createPurchasesForMerchant(Merchant $merchant): void
    {
        $businesses = Business::where('merchant_id', $merchant->id)->get();
        if ($businesses->isEmpty()) {
            return;
        }

        $products = Product::where('merchant_id', $merchant->id)
            ->where('is_active', true)
            ->get();

        if ($products->isEmpty()) {
            $this->command->warn("No products found for merchant: {$merchant->name}");

            return;
        }

        // Get a staff user to set as created_by (use first staff user for merchant, or null)
        $createdBy = User::where('merchant_id', $merchant->id)->first();

        // Create 5-10 purchases per merchant
        $purchaseCount = rand(5, 10);

        for ($i = 1; $i <= $purchaseCount; $i++) {
            $business = $businesses->random();
            $branches = Branch::where('business_id', $business->id)->get();

            if ($branches->isEmpty()) {
                continue;
            }

            $branch = $branches->random();

            // Random date within last 30 days
            $purchaseDate = now()->subDays(rand(0, 30));

            $purchaseNo = 'PUR-'.$purchaseDate->format('Ymd').'-'.strtoupper(substr(uniqid(), -6));

            $purchase = Purchase::firstOrCreate(
                [
                    'purchase_no' => $purchaseNo,
                ],
                [
                    'id' => Str::uuid(),
                    'merchant_id' => $merchant->id,
                    'business_id' => $business->id,
                    'branch_id' => $branch->id,
                    'purchase_date' => $purchaseDate,
                    'subtotal' => 0,
                    'discount' => 0,
                    'tax' => 0,
                    'total_amount' => 0,
                    'notes' => "Purchase order #{$i} for {$business->name}",
                    'created_by' => $createdBy?->id,
                ]
            );

            // Create 2-5 purchase items
            $itemCount = rand(2, 5);
            $selectedProducts = $products->random(min($itemCount, $products->count()));

            $subtotal = 0;

            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 10);
                $unitPrice = $product->purchase_price ?? rand(1000, 50000) / 100; // Random price if not set
                $lineTotal = $quantity * $unitPrice;
                $subtotal += $lineTotal;

                PurchaseItem::firstOrCreate(
                    [
                        'purchase_id' => $purchase->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'id' => Str::uuid(),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ]
                );
            }

            // Calculate totals
            $discount = rand(0, 10) > 7 ? rand(100, 1000) / 100 : 0; // 30% chance of discount
            $tax = $subtotal * 0.15; // 15% tax
            $totalAmount = $subtotal - $discount + $tax;

            $purchase->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total_amount' => $totalAmount,
            ]);
        }

        $this->command->info("Created {$purchaseCount} purchases for merchant: {$merchant->name}");
    }
}
