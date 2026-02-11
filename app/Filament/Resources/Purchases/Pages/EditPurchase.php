<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Purchases\PurchaseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;


    public function getTitle(): string
    {
        $name = (string) ($this->record?->name ?? '');

        return 'Edit ' . \Illuminate\Support\Str::limit($name, 30);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items->map(fn ($item) => [
            'line_subtotal'      => (float) $item->line_total,
            'discount_amount'    => (float) $item->line_total * ((float) ($item->discount ?? 0) / 100),
            'tax_amount'         => ((float) $item->line_total - ((float) $item->line_total * ((float) ($item->discount ?? 0) / 100)))
                * ((float) ($item->tax ?? 0) / 100),
            'branch_id'          => $item->branch_id,
            'product_id'         => $item->product_id,
            'product_variant_id' => optional($item->variants->first())->product_variant_id,
            'quantity'           => $item->quantity,
            'unit_price'         => $item->unit_price,
            'line_total'         => $item->line_total,
            'discount'           => $item->discount ?? 0,
            'tax'                => $item->tax ?? 0,
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
        $items = self::normalizeItems($items);

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

        return $data;
    }


    protected function afterSave(): void
    {
        DB::transaction(function () {

            $items = $this->form->getState()['items'] ?? [];
            $items = self::normalizeItems($items);

            // Clear existing
            $this->record->items()->delete();

            foreach ($items as $item) {

                $branch = \App\Models\Branch::select('id', 'business_id')
                    ->find($item['branch_id']);

                if (! $branch) {
                    continue;
                }

                $purchaseItem = $this->record->items()->create([
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
        });
    }

    private static function normalizeItems(array $items): array
    {
        foreach ($items as &$item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $lineSubtotal = $qty * $unitPrice;
            $lineTotal = $lineSubtotal;

            $discountRate = (float) ($item['discount'] ?? 0);
            $discountAmount = (float) ($item['discount_amount'] ?? 0);

            if ($discountAmount > 0 && $lineTotal > 0) {
                $discountRate = ($discountAmount / $lineTotal) * 100;
            }

            $discountRate = max(0, min(100, $discountRate));
            $discountAmount = $lineTotal * ($discountRate / 100);

            $taxRate = (float) ($item['tax'] ?? 0);
            $taxAmount = (float) ($item['tax_amount'] ?? 0);

            $taxableAmount = $lineTotal - $discountAmount;

            if ($taxAmount > 0 && $taxableAmount > 0) {
                $taxRate = ($taxAmount / $taxableAmount) * 100;
            }

            $taxRate = max(0, min(100, $taxRate));

            $item['line_total'] = $lineTotal;
            $item['discount'] = round($discountRate, 2);
            $item['tax'] = round($taxRate, 2);
        }

        return $items;
    }



}
