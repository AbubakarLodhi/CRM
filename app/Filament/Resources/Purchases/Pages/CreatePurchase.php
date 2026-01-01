<?php

namespace App\Filament\Resources\Purchases\Pages;

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

            // If merchant is creating, ensure merchant_id set
            $user = Filament::auth()->user();
            if ($user && !($user instanceof \App\Models\Admin)) {
                $data['merchant_id'] = $user->id;
            }

            $subtotal = collect($items)->sum(fn ($i) => (float)($i['line_total'] ?? 0));
            $discount = (float)($data['discount'] ?? 0);
            $tax = (float)($data['tax'] ?? 0);

            $data['subtotal'] = $subtotal;
            $data['total_amount'] = $subtotal - $discount + $tax;

            $purchase = static::getModel()::create($data);

            foreach ($items as $item) {
                $purchase->items()->create($item);
            }

            return $purchase;
        });
    }
}
