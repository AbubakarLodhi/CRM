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

            $items = $data['items'] ?? [];
            unset($data['items']);

            $user = Filament::auth()->user();

            if ($user instanceof \App\Models\Merchant) {
                $data['merchant_id'] = $user->id;
                $data['created_by']  = null;
            }

            if ($user instanceof \App\Models\User) {
                $data['merchant_id'] = $user->merchant_id;
                $data['created_by']  = $user->id;
            }

            $subtotal = collect($items)->sum(fn ($i) => (float) ($i['line_total'] ?? 0));
            $discount = (float) ($data['discount'] ?? 0);
            $tax      = (float) ($data['tax'] ?? 0);

            $data['subtotal']     = $subtotal;
            $data['total_amount'] = $subtotal - $discount + $tax;

            /** -------------------------
             * CREATE SALE
             * ------------------------- */
            $sale = static::getModel()::create($data);

            foreach ($items as $item) {
                $sale->items()->create($item);
            }

            /** -------------------------
             * CREATE ORDER
             * ------------------------- */
            \App\Models\Order::create([
                'merchant_id' => $sale->merchant_id,
                'business_id' => $sale->business_id,
                'branch_id'   => $sale->branch_id,
                'sale_id'     => $sale->id,
                'status'      => 'pending',
            ]);

            /** -------------------------
             * SAVE MERCHANT LOGO
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
