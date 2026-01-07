<?php

namespace App\Filament\Resources\Sales\Pages;

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

            $panel = Filament::getCurrentPanel();
            $guard = $panel?->getAuthGuard();
            $user  = Filament::auth()->user();

            /*
            |--------------------------------------------------------------------------
            | created_by logic
            |--------------------------------------------------------------------------
            | Only staff panel should set created_by
            */
            if ($guard === 'staff' && $user) {
                $data['created_by'] = $user->id;
            } else {
                $data['created_by'] = null;
            }
            if ($user) {
                $data['merchant_id'] = $user->id;
            }

            $subtotal = collect($items)->sum(fn ($i) => (float) ($i['line_total'] ?? 0));
            $discount = (float) ($data['discount'] ?? 0);
            $tax = (float) ($data['tax'] ?? 0);

            $data['subtotal'] = $subtotal;
            $data['total_amount'] = $subtotal - $discount + $tax;

            $sale = static::getModel()::create($data);

            foreach ($items as $item) {
                $sale->items()->create($item);
            }

            // Create order automatically when sale is created
            \App\Models\Order::create([
                'merchant_id' => $sale->merchant_id,
                'business_id' => $sale->business_id,
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'status' => 'pending',
            ]);

            return $sale;
        });
    }
}
