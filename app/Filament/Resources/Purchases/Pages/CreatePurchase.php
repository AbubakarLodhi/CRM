<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Purchases\PurchaseResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {

            /* -----------------------------
             | EXTRACT ITEMS
             ----------------------------- */
            $items = $data['items'] ?? [];
            unset($data['items']);

            /* -----------------------------
             | CREATED BY
             ----------------------------- */
            $panel = Filament::getCurrentPanel();
            $guard = $panel?->getAuthGuard();
            $user  = Filament::auth()->user();

            $data['created_by'] = ($guard === 'staff' && $user)
                ? $user->id
                : null;

            /* -----------------------------
             | TOTALS
             ----------------------------- */
            $subtotal = collect($items)->sum(fn ($i) => (float) ($i['line_total'] ?? 0));
            $totalDiscount = 0.0;
            $totalTax = 0.0;

            foreach ($items as $item) {
                $lineTotal = (float) ($item['line_total'] ?? 0);
                $discountRate = (float) ($item['discount'] ?? 0);
                $taxRate = (float) ($item['tax'] ?? 0);

                $discountRate = max(0, min(100, $discountRate));
                $taxRate = max(0, min(100, $taxRate));

                $discountAmount = $lineTotal * ($discountRate / 100);
                $taxableAmount = $lineTotal - $discountAmount;
                $taxAmount = $taxableAmount * ($taxRate / 100);

                $totalDiscount += $discountAmount;
                $totalTax += $taxAmount;
            }

            $data['subtotal']     = $subtotal;
            $data['total_amount'] = $subtotal - $totalDiscount + $totalTax;

            /* -----------------------------
             | CREATE PURCHASE
             ----------------------------- */
            $purchase = static::getModel()::create($data);

            /* -----------------------------
             | CREATE ITEMS + VARIANTS ✅
             ----------------------------- */
            foreach ($items as $item) {

                $branch = \App\Models\Branch::select('id', 'business_id')
                    ->find($item['branch_id']);

                if (! $branch) {
                    continue;
                }

                $purchaseItem = $purchase->items()->create([
                    'business_id' => $branch->business_id,
                    'branch_id'   => $branch->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'line_total'  => $item['line_total'],
                    'discount'    => $item['discount'] ?? 0,
                    'tax'         => $item['tax'] ?? 0,
                ]);

                // ✅ MATCH SALE
                if (! empty($item['product_variant_id'])) {
                    $purchaseItem->variants()->create([
                        'product_variant_id' => $item['product_variant_id'],
                        'quantity'           => $item['quantity'],
                        'unit_price'         => $item['unit_price'],
                        'line_total'         => $item['line_total'],
                    ]);
                }
            }

            /* -----------------------------
             | MERCHANT LOGO (UNCHANGED)
             ----------------------------- */
            $state = $this->form->getRawState();

            if (array_key_exists('merchant_logo', $state)) {
                $merchant = Filament::auth()->user() instanceof \App\Models\Merchant
                    ? Filament::auth()->user()
                    : Filament::auth()->user()?->merchant;

                if ($merchant && ($logo = collect($state['merchant_logo'])->first())) {
                    $merchant->logo()?->delete();

                    $merchant->logo()->create([
                        'merchant_id' => $merchant->id,
                        'type'        => AttachmentType::IMAGE,
                        'meta_type'   => AttachmentMetaType::MERCHANT_LOGO,
                        'photo_url'   => $logo,
                    ]);
                }
            }

            return $purchase;
        });
    }
}
