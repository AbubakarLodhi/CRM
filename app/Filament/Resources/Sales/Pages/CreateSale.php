<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Sales\SaleResource;
use App\Mail\SaleCreatedMailable;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            $items = self::normalizeItems($items);

            /** -------------------------
             * MERCHANT / CREATED BY
             * ------------------------- */
            $panel = Filament::getCurrentPanel();
            $guard = $panel?->getAuthGuard();
            $user  = $guard ? auth($guard)->user() : Filament::auth()->user();

            if ($guard === 'staff' && $user instanceof \App\Models\User) {
                $data['merchant_id'] = $user->merchant_id;
                $data['created_by']  = $user->id;
            } elseif ($user instanceof \App\Models\Merchant) {
                $data['merchant_id'] = $user->id;
                $data['created_by']  = null;
            } elseif ($user instanceof \App\Models\User) {
                $data['merchant_id'] = $user->merchant_id;
                $data['created_by']  = $user->id;
            }

            /** -------------------------
             * TOTALS (UNCHANGED)
             * ------------------------- */
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
                    'discount'    => $item['discount'] ?? 0,
                    'tax'         => $item['tax'] ?? 0,
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

    protected function afterCreate(): void
    {
        $sale = $this->record;
        $customerEmail = $sale->customer?->email;

        if (! $customerEmail) {
            Log::info('SaleCreated email skipped: customer email missing.', [
                'sale_id' => $sale->id,
                'merchant_id' => $sale->merchant_id,
                'customer_id' => $sale->customer_id,
            ]);
            return;
        }

        $mailable = new SaleCreatedMailable($sale);

        if (! $mailable->hasTemplate()) {
            Log::info('SaleCreated email skipped: no active template found.', [
                'sale_id' => $sale->id,
                'merchant_id' => $sale->merchant_id,
                'customer_id' => $sale->customer_id,
            ]);
            return;
        }

        Log::info('SaleCreated email sending.', [
            'sale_id' => $sale->id,
            'merchant_id' => $sale->merchant_id,
            'customer_id' => $sale->customer_id,
            'to' => $customerEmail,
            'template_id' => $mailable->template?->id,
        ]);

        Mail::to($customerEmail)->queue($mailable);

        Log::info('SaleCreated email sent.', [
            'sale_id' => $sale->id,
            'merchant_id' => $sale->merchant_id,
            'customer_id' => $sale->customer_id,
            'to' => $customerEmail,
            'template_id' => $mailable->template?->id,
        ]);
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
