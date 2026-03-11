<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Services\PaymentLedgerService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;
    protected bool $isPartiallyReturned = false;
    protected float $paymentDelta = 0.0;
    protected ?string $paymentDate = null;


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
        $this->isPartiallyReturned = $this->isPartiallyReturnedPurchase();

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

        if (! array_key_exists('paid_amount', $data) || $data['paid_amount'] === null) {
            $totalAmount = (float) ($data['total_amount'] ?? 0);
            $paidAmount = strtolower((string) ($data['payment_type'] ?? 'cash')) === 'cash'
                ? $totalAmount
                : 0;
            $data['paid_amount'] = round($paidAmount, 2);
        }

        if (! array_key_exists('due_amount', $data) || $data['due_amount'] === null) {
            $totalAmount = (float) ($data['total_amount'] ?? 0);
            $paidAmount = (float) ($data['paid_amount'] ?? 0);
            $data['due_amount'] = round(max(0, $totalAmount - $paidAmount), 2);
        }

        $data['payment_date'] = now()->toDateString();
        $data['is_partial_return'] = $this->isPartiallyReturned;

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
        if ($this->isPartiallyReturnedPurchase()) {
            $this->isPartiallyReturned = true;
            $this->preparePaymentDelta($data, (float) ($this->record->total_amount ?? 0));

            $lockedData = [
                'total_amount' => (float) ($this->record->total_amount ?? 0),
                'paid_amount' => $data['paid_amount'] ?? $this->record->paid_amount,
            ];

            self::applyPaymentFields($lockedData);

            return [
                'paid_amount' => $lockedData['paid_amount'],
                'due_amount' => $lockedData['due_amount'],
                'payment_type' => $lockedData['payment_type'],
            ];
        }

        $this->preparePaymentDelta($data);

        $items = $data['items'] ?? [];
        unset($data['items']);
        unset($data['payment_date']);
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
        self::applyPaymentFields($data);

        return $data;
    }


    protected function afterSave(): void
    {
        if ($this->isPartiallyReturned && $this->paymentDelta <= 0) {
            return;
        }

        DB::transaction(function () {

            $items = $this->form->getState()['items'] ?? [];
            $items = self::normalizeItems($items);

            if (! $this->isPartiallyReturned) {
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
            }

            if ($this->paymentDelta > 0) {
                PaymentLedgerService::recordPurchasePayment(
                    $this->record->fresh(),
                    $this->paymentDelta,
                    $this->paymentDate,
                );
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

    private static function applyPaymentFields(array &$data): void
    {
        $totalAmount = max(0, (float) ($data['total_amount'] ?? 0));
        $paidAmount = $data['paid_amount'] ?? null;

        $paidAmount = $paidAmount === null || $paidAmount === ''
            ? $totalAmount
            : (float) $paidAmount;

        $paidAmount = max(0, min($totalAmount, $paidAmount));
        $dueAmount = max(0, $totalAmount - $paidAmount);

        $data['paid_amount'] = round($paidAmount, 2);
        $data['due_amount'] = round($dueAmount, 2);
        $data['payment_type'] = $dueAmount > 0 ? 'credit' : 'cash';
    }

    public function isPartiallyReturnedPurchase(): bool
    {
        if (! $this->record?->returns()->exists()) {
            return false;
        }

        foreach ($this->record->items as $item) {
            $itemQty = (int) ($item->quantity ?? 0);
            $variantQty = (int) $item->variants->sum('quantity');

            if (max($itemQty, $variantQty) > 0) {
                return true;
            }
        }

        return false;
    }

    protected function preparePaymentDelta(array $data, ?float $forcedTotalAmount = null): void
    {
        $recordedPaid = $this->record->payments()->sum('amount');
        if ((float) $recordedPaid <= 0 && ! $this->record->payments()->exists()) {
            $recordedPaid = (float) ($this->record->paid_amount ?? 0);
        }

        $totalAmount = $forcedTotalAmount ?? (float) ($data['total_amount'] ?? $this->record->total_amount ?? 0);
        $desiredPaid = (float) ($data['paid_amount'] ?? $this->record->paid_amount ?? 0);
        $desiredPaid = max(0, min($totalAmount, $desiredPaid));
        $delta = round($desiredPaid - (float) $recordedPaid, 2);

        if ($delta < 0) {
            throw ValidationException::withMessages([
                'data.paid_amount' => 'Paid amount cannot be reduced because payment history is now tracked in installments.',
            ]);
        }

        $this->paymentDelta = $delta;
        $this->paymentDate = filled($data['payment_date'] ?? null)
            ? (string) $data['payment_date']
            : now()->toDateString();
    }



}
