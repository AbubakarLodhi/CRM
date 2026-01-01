<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'line_total' => $item->line_total,
        ])->toArray();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        return [
            ViewAction::make()
                ->visible(fn () => auth($guard)->user()?->hasPermissionTo('purchases.view', $guard)),

            DeleteAction::make()
                ->color('danger')
                ->visible(fn () => auth($guard)->user()?->hasPermissionTo('purchases.delete', $guard)),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $subtotal = collect($items)->sum(fn ($i) => (float)($i['line_total'] ?? 0));
        $discount = (float)($data['discount'] ?? 0);
        $tax = (float)($data['tax'] ?? 0);

        $data['subtotal'] = $subtotal;
        $data['total_amount'] = $subtotal - $discount + $tax;

        return $data;
    }

    protected function afterSave(): void
    {
        DB::transaction(function () {
            $items = $this->form->getState()['items'] ?? [];

            $this->record->items()->delete();

            foreach ($items as $item) {
                $this->record->items()->create($item);
            }
        });
    }
}
