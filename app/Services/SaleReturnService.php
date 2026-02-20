<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleReturnService
{
    public static function createReturn(Sale $sale, array $data): void
    {
        DB::transaction(function () use ($sale, $data) {

            $sale->loadMissing('items.product', 'items.variants');

            $returnItems = [];
            $subtotal = 0.0;
            $totalDiscount = 0.0;
            $totalTax = 0.0;

            foreach ($data['items'] as $item) {

                if ($item['quantity'] <= 0) {
                    continue;
                }

                $saleItem = $sale->items()
                    ->where('id', $item['sale_item_id'])
                    ->first();

                if (! $saleItem) {
                    continue;
                }

                $returnedQty = (int) SaleReturnItem::query()
                    ->where('sale_item_id', $saleItem->id)
                    ->sum('quantity');

                $remainingQty = (int) $saleItem->quantity - $returnedQty;

                if ($item['quantity'] > $remainingQty) {
                    $productName = $saleItem->product?->name ?? 'Product';
                    throw new \Exception(
                        "Return quantity for {$productName} cannot exceed remaining quantity ({$remainingQty})."
                    );
                }

                $lineTotal = (float) $saleItem->unit_price * (int) $item['quantity'];
                $discountRate = (float) ($saleItem->discount ?? 0);
                $taxRate = (float) ($saleItem->tax ?? 0);
                $discountAmount = $lineTotal * ($discountRate / 100);
                $taxableAmount = $lineTotal - $discountAmount;
                $taxAmount = $taxableAmount * ($taxRate / 100);

                $variantAllocations = [];
                $variantRows = $saleItem->variants;
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

                        $variantUnit = (float) ($variantRow->unit_price ?? $saleItem->unit_price);
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
                        'sale_item_id' => $saleItem->id,
                        'business_id'  => $saleItem->business_id,
                        'branch_id'    => $saleItem->branch_id,
                        'product_id'   => $saleItem->product_id,
                        'quantity'     => $returnQty,
                        'unit_price'   => $saleItem->unit_price,
                        'line_total'   => $lineTotal,
                        'discount'     => $saleItem->discount,
                        'tax'          => $saleItem->tax,
                    ],
                    'variants' => $variantAllocations,
                ];

                $subtotal += $lineTotal;
                $totalDiscount += $discountAmount;
                $totalTax += $taxAmount;

                // ❌ REMOVE direct stock update
            }

            if (! $returnItems) {
                throw new \Exception('No return items with quantity greater than zero.');
            }

            $return = SaleReturn::create([
                'merchant_id' => $sale->merchant_id,
                'sale_id'     => $sale->id,
                'customer_id' => $sale->customer_id,
                'return_no'   => 'RET-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
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

    public static function deleteReturn(SaleReturn $return): void
    {
        DB::transaction(function () use ($return) {
            $return->loadMissing('items.variants');

            foreach ($return->items as $item) {
                $item->variants()->delete();
                $item->delete();
            }

            $return->delete();
        });
    }
}
