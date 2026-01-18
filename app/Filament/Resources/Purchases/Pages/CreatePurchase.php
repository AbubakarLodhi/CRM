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

            $items = $data['items'] ?? [];
            unset($data['items']);

            $panel = Filament::getCurrentPanel();
            $guard = $panel?->getAuthGuard();
            $user = Filament::auth()->user();

            if ($guard === 'staff' && $user) {
                $data['created_by'] = $user->id;
            } else {
                $data['created_by'] = null;
            }

            $subtotal = collect($items)->sum(fn($i) => (float)($i['line_total'] ?? 0));
            $discount = (float)($data['discount'] ?? 0);
            $tax = (float)($data['tax'] ?? 0);

            $data['subtotal'] = $subtotal;
            $data['total_amount'] = $subtotal - $discount + $tax;

            /** --------------------------------
             * CREATE PURCHASE
             * -------------------------------- */
            $purchase = static::getModel()::create($data);

            foreach ($items as $item) {
                $purchase->items()->create($item);
            }

            /** --------------------------------
             * SAVE MERCHANT LOGO (NEW)
             * -------------------------------- */
            $state = $this->form->getRawState();

            if (array_key_exists('merchant_logo', $state)) {
                $merchant = Filament::auth()->user() instanceof \App\Models\Merchant
                    ? Filament::auth()->user()
                    : Filament::auth()->user()?->merchant;

                if ($merchant) {
                    if ($logo = collect($state['merchant_logo'])->first()) {
                        $merchant->logo()?->delete();

                        $merchant->logo()->create([
                            'merchant_id' => $merchant->id,
                            'type' => AttachmentType::IMAGE,
                            'meta_type' => AttachmentMetaType::MERCHANT_LOGO,
                            'photo_url' => $logo,
                        ]);
                    }
                }
            }

            return $purchase;
        });
    }
}
