<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseReturnService
{
    public static function createReturn(Purchase $purchase, array $data): void
    {
        DB::transaction(function () use ($purchase, $data) {

            $purchase->loadMissing('items.product', 'items.variants');

            $returnItems = [];
            $subtotal = 0.0;
            $totalDiscount = 0.0;
            $totalTax = 0.0;

            foreach ($data['items'] as $item) {

                if ($item['quantity'] <= 0) {
                    continue;
                }

                $purchaseItem = $purchase->items()
                    ->where('id', $item['purchase_item_id'])
                    ->first();

                if (! $purchaseItem) {
                    continue;
                }

                $returnedQty = (int) PurchaseReturnItem::query()
                    ->where('purchase_item_id', $purchaseItem->id)
                    ->sum('quantity');

                $remainingQty = (int) $purchaseItem->quantity - $returnedQty;

                if ($item['quantity'] > $remainingQty) {
                    $productName = $purchaseItem->product?->name ?? 'Product';
                    throw new \Exception(
                        "Return quantity for {$productName} cannot exceed remaining quantity ({$remainingQty})."
                    );
                }

                $lineTotal = (float) $purchaseItem->unit_price * (int) $item['quantity'];
                $discountRate = (float) ($purchaseItem->discount ?? 0);
                $taxRate = (float) ($purchaseItem->tax ?? 0);
                $discountAmount = $lineTotal * ($discountRate / 100);
                $taxableAmount = $lineTotal - $discountAmount;
                $taxAmount = $taxableAmount * ($taxRate / 100);

                $variantAllocations = [];
                $variantRows = $purchaseItem->variants;
                $returnQty = (int) $item['quantity'];

                if ($variantRows->isNotEmpty()) {
                    $totalVariantQty = (int) $variantRows->sum('quantity');
                    $remaining = $returnQty;
                    $count = $variantRows->count();

                    foreach ($variantRows->values() as $index => $variantRow) {
                        if ($index === $count - 1) {
                            $allocQty = $remaining;
                        } else {
                            $ratio = $totalVariantQty > 0 ? ($variantRow->quantity / $totalVariantQty) : 0;
                            $allocQty = (int) floor($returnQty * $ratio);
                            $allocQty = min($allocQty, $remaining);
                        }

                        $remaining -= $allocQty;

                        if ($allocQty <= 0) {
                            continue;
                        }

                        $variantUnit = (float) ($variantRow->unit_price ?? $purchaseItem->unit_price);
                        $variantAllocations[] = [
                            'product_variant_id' => $variantRow->product_variant_id,
                            'quantity' => $allocQty,
                            'unit_price' => $variantUnit,
                            'line_total' => $variantUnit * $allocQty,
                        ];
                    }
                }

                $returnItems[] = [
                    'data' => [
                        'purchase_item_id' => $purchaseItem->id,
                        'business_id'  => $purchaseItem->business_id,
                        'branch_id'    => $purchaseItem->branch_id,
                        'product_id'   => $purchaseItem->product_id,
                        'quantity'     => $returnQty,
                        'unit_price'   => $purchaseItem->unit_price,
                        'line_total'   => $lineTotal,
                        'discount'     => $purchaseItem->discount,
                        'tax'          => $purchaseItem->tax,
                    ],
                    'variants' => $variantAllocations,
                ];

                $subtotal += $lineTotal;
                $totalDiscount += $discountAmount;
                $totalTax += $taxAmount;
            }

            if (! $returnItems) {
                throw new \Exception('No return items with quantity greater than zero.');
            }

            $return = PurchaseReturn::create([
                'merchant_id' => $purchase->merchant_id,
                'purchase_id' => $purchase->id,
                'return_no'   => 'PRET-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'return_date' => $data['return_date'],
                'subtotal'    => $subtotal,
                'total_discount' => $totalDiscount,
                'total_tax'   => $totalTax,
                'total_amount' => $subtotal - $totalDiscount + $totalTax,
                'reason'      => $data['reason'],
                'created_by'  => auth()->id(),
            ]);

            foreach ($returnItems as $returnItem) {
                $itemModel = $return->items()->create($returnItem['data']);

                if (! empty($returnItem['variants'])) {
                    $itemModel->variants()->createMany($returnItem['variants']);
                }
            }
        });
    }
}
