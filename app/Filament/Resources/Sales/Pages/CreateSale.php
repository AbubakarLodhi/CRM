<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Sales\SaleResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {

            /** -------------------------
             * EXTRACT ITEMS
             * ------------------------- */
            $items = $data['items'] ?? [];
            unset($data['items']);

            /** -------------------------
             * MERCHANT / CREATED BY
             * ------------------------- */
            $user = Filament::auth()->user();

            if ($user instanceof \App\Models\Merchant) {
                $data['merchant_id'] = $user->id;
                $data['created_by']  = null;
            }

            if ($user instanceof \App\Models\User) {
                $data['merchant_id'] = $user->merchant_id;
                $data['created_by']  = $user->id;
            }

            /** -------------------------
             * TOTALS (UNCHANGED)
             * ------------------------- */
            $subtotal = collect($items)->sum(fn ($i) => (float) ($i['line_total'] ?? 0));
            $discount = (float) ($data['discount'] ?? 0);
            $tax      = (float) ($data['tax'] ?? 0);

            $data['subtotal']     = $subtotal;
            $data['total_amount'] = $subtotal - $discount + $tax;

            /** -------------------------
             * CREATE SALE (UNCHANGED)
             * ------------------------- */
            $sale = static::getModel()::create($data);

            /** -------------------------
             * CREATE SALE ITEMS
             * ( DO NOT INSERT product_variant_id HERE)
             * ------------------------- */
            foreach ($items as $item) {

                $branch = \App\Models\Branch::select('id', 'business_id')
                    ->find($item['branch_id']);

                if (! $branch) {
                    continue;
                }

                $saleItem = $sale->items()->create([
                    'business_id' => $branch->business_id, // ✅ DERIVED
                    'branch_id'   => $branch->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'line_total'  => $item['line_total'],
                ]);

                if (! empty($item['product_variant_id'])) {
                    $saleItem->variants()->create([
                        'product_variant_id' => $item['product_variant_id'],
                        'quantity'           => $item['quantity'],
                        'unit_price'         => $item['unit_price'],
                        'line_total'         => $item['line_total'],
                    ]);
                }
            }


            /** -------------------------
             * CREATE ORDER (UNCHANGED)
             * ------------------------- */
            $firstItem = $sale->items()->first();

            if ($firstItem) {
                \App\Models\Order::create([
                    'merchant_id' => $sale->merchant_id,
                    'sale_id'     => $sale->id,
                    'status'      => 'pending',
                ]);
            }

            /** -------------------------
             * SAVE MERCHANT LOGO (UNCHANGED)
             * ------------------------- */
            $state = $this->form->getRawState();

            if (array_key_exists('merchant_logo', $state)) {
                $merchant = $user instanceof \App\Models\Merchant
                    ? $user
                    : $user?->merchant;

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

            return $sale;
        });
    }
}
