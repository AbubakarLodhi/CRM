<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Sales\SaleResource;
use App\Services\Notifications\NotificationDispatcher;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Services\PaymentLedgerService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Throwable;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    // ─── POS toggle ───────────────────────────────────────────────
    public string $viewMode = 'standard'; // 'standard' | 'pos'

    // POS state
    public array   $posCart          = [];
    public ?string $posCustomerId    = null;
    public string  $posDiscountMode  = 'percent';
    public string  $posPaymentMethod = 'cash';
    public float   $posPaidAmount    = 0.0;
    public string  $posSaleNo        = '';
    public string  $posSaleDate      = '';

    public function mount(): void
    {
        parent::mount();
        $this->posSaleNo   = 'SAL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $this->posSaleDate = now()->format('Y-m-d');

    }

    // ─── View toggle ──────────────────────────────────────────────
    public function switchToPos(): void
    {
        $this->viewMode = 'pos';
    }

    public function switchToStandard(): void
    {
        $this->viewMode = 'standard';
    }

    // ─── POS helpers ──────────────────────────────────────────────
    public function getPosProducts(?string $search = null): array
    {
        $user = Filament::auth()->user();
        $merchantId = $user instanceof \App\Models\Merchant
            ? $user->id
            : $user?->merchant_id;

        $query = Product::query()
            ->withoutTrashed()
            ->where('is_active', true)
            ->where('merchant_id', $merchantId);

        if (filled($search)) {
            $term = '%' . mb_strtolower(trim($search)) . '%';
            $query->where(fn ($q) =>
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(sku) LIKE ?', [$term])
            );
        }

        return $query->limit(50)->get(['id', 'name', 'sku'])->toArray();
    }

    public function getPosVariants(string $productId): array
    {
        return ProductVariant::query()
            ->withoutTrashed()
            ->where('product_id', $productId)
            ->limit(50)
            ->get(['id', 'name', 'sku', 'selling_price'])
            ->toArray();
    }

    public function getPosBranches(string $productId): array
    {
        $user = Filament::auth()->user();
        $merchantId = $user instanceof \App\Models\Merchant
            ? $user->id
            : $user?->merchant_id;

        $hasBranchAssignments = \Illuminate\Support\Facades\DB::table('branch_products')
            ->where('product_id', $productId)
            ->exists();

        $query = Branch::query()
            ->withoutTrashed()
            ->where('merchant_id', $merchantId);

        if ($hasBranchAssignments) {
            $query->whereExists(fn ($q) =>
                $q->selectRaw(1)
                  ->from('branch_products')
                  ->whereColumn('branch_products.branch_id', 'branches.id')
                  ->where('branch_products.product_id', $productId)
            );
        }

        return $query->orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function posAddItem(string $productId, string $variantId, string $branchId): void
    {
        $variant = ProductVariant::find($variantId);
        if (! $variant) return;

        $key = $productId . '_' . $variantId . '_' . $branchId;
        if (isset($this->posCart[$key])) {
            $this->posCart[$key]['quantity']++;
        } else {
            $product = Product::find($productId);
            $branch  = Branch::find($branchId);
            $this->posCart[$key] = [
                'key'                => $key,
                'product_id'         => $productId,
                'product_name'       => $product?->name ?? '',
                'product_variant_id' => $variantId,
                'variant_name'       => $variant->name ?? $variant->sku ?? '',
                'branch_id'          => $branchId,
                'branch_name'        => $branch?->name ?? '',
                'quantity'           => 1,
                'unit_price'         => (float) ($variant->selling_price ?? 0),
                'discount'           => 0,
                'discount_amount'    => 0,
                'tax'                => 0,
                'tax_amount'         => 0,
                'line_total'         => (float) ($variant->selling_price ?? 0),
            ];
        }
        $this->recalcPosCart();
    }

    public function posUpdateQty(string $key, int $delta): void
    {
        if (! isset($this->posCart[$key])) return;

        $newQty = $this->posCart[$key]['quantity'] + $delta;

        if ($newQty <= 0) {
            // Remove item when quantity hits zero via the minus button
            unset($this->posCart[$key]);
            $this->recalcPosCart();
            return;
        }

        $this->posCart[$key]['quantity'] = $newQty;
        $this->recalcPosItem($key);
        $this->recalcPosCart();
    }

    public function posRemoveItem(string $key): void
    {
        unset($this->posCart[$key]);
        // Do NOT call array_values() — it converts string keys to numeric indices,
        // which breaks all subsequent posUpdateQty / posRemoveItem calls.
        $this->recalcPosCart();
    }

    public function posClearCart(): void
    {
        $this->posCart = [];
    }

    public function posUpdateField(string $key, string $field, $value): void
    {
        if (! isset($this->posCart[$key])) return;
        $this->posCart[$key][$field] = $value;
        $this->recalcPosItem($key);
        $this->recalcPosCart();
    }

    private function recalcPosItem(string $key): void
    {
        $item     = &$this->posCart[$key];
        $qty      = (float) ($item['quantity'] ?? 1);
        $unit     = (float) ($item['unit_price'] ?? 0);
        $subtotal = $qty * $unit;
        $discRate = (float) ($item['discount'] ?? 0);
        $taxRate  = (float) ($item['tax'] ?? 0);

        $discAmt = $subtotal * ($discRate / 100);
        $taxable = max(0, $subtotal - $discAmt);
        $taxAmt  = $taxable * ($taxRate / 100);

        $item['discount_amount'] = round($discAmt, 2);
        $item['tax_amount']      = round($taxAmt, 2);
        $item['line_total']      = round($taxable + $taxAmt, 2);
    }

    private function recalcPosCart(): void
    {
        foreach (array_keys($this->posCart) as $key) {
            $this->recalcPosItem($key);
        }
    }

    public function getPosSubtotal(): float
    {
        return collect($this->posCart)->sum(fn ($i) => (float) ($i['quantity'] ?? 0) * (float) ($i['unit_price'] ?? 0));
    }

    public function getPosDiscount(): float
    {
        return collect($this->posCart)->sum(fn ($i) => (float) ($i['discount_amount'] ?? 0));
    }

    public function getPosTax(): float
    {
        return collect($this->posCart)->sum(fn ($i) => (float) ($i['tax_amount'] ?? 0));
    }

    public function getPosTotal(): float
    {
        return $this->getPosSubtotal() - $this->getPosDiscount() + $this->getPosTax();
    }

    // ─── POS submit ───────────────────────────────────────────────
    public function posSubmit(): void
    {
        if (empty($this->posCart) || ! $this->posCustomerId) {
            $this->addError('pos', 'Please select a customer and add at least one item.');
            return;
        }

        $items = array_values($this->posCart);

        $data = [
            'sale_no'        => $this->posSaleNo,
            'sale_date'      => $this->posSaleDate,
            'customer_id'    => $this->posCustomerId,
            'payment_method' => $this->posPaymentMethod,
            'paid_amount'    => $this->posPaidAmount,
            'items'          => $items,
        ];

        $sale = $this->handleRecordCreation($data);

        // Reset cart
        $this->posCart          = [];
        $this->posCustomerId    = null;
        $this->posPaidAmount    = 0.0;
        $this->posPaymentMethod = 'cash';
        $this->posSaleNo        = 'SAL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        // Show post-order modal
        $this->dispatch('pos-order-placed', saleId: $sale->id, saleNo: $sale->sale_no);
    }

    // ─── POS submit and create another ────────────────────────────
    public function posSubmitAndCreateAnother(): void
    {
        if (empty($this->posCart) || ! $this->posCustomerId) {
            $this->addError('pos', 'Please select a customer and add at least one item.');
            return;
        }

        $items = array_values($this->posCart);

        $data = [
            'sale_no'        => $this->posSaleNo,
            'sale_date'      => $this->posSaleDate,
            'customer_id'    => $this->posCustomerId,
            'payment_method' => $this->posPaymentMethod,
            'paid_amount'    => $this->posPaidAmount,
            'items'          => $items,
        ];

        $this->handleRecordCreation($data);

        // Reset everything for next sale
        $this->posCart          = [];
        $this->posCustomerId    = null;
        $this->posPaidAmount    = 0.0;
        $this->posPaymentMethod = 'cash';
        $this->posDiscountMode  = 'percent';
        $this->posSaleNo        = 'SAL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $this->posSaleDate      = now()->format('Y-m-d');

        \Filament\Notifications\Notification::make()
            ->title('Sale created! Cart cleared for next order.')
            ->success()
            ->send();
    }

    // ─── Header actions (toggle buttons) ─────────────────────────
    protected function getHeaderActions(): array
    {
        return [
            Action::make('switchToPos')
                ->label('POS view')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->visible(fn () => $this->viewMode === 'standard')
                ->action(fn () => $this->switchToPos()),

            Action::make('switchToStandard')
                ->label('Standard view')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn () => $this->viewMode === 'pos')
                ->action(fn () => $this->switchToStandard()),
        ];
    }

    // ─── Render ───────────────────────────────────────────────────
    public function getView(): string
    {
        if ($this->viewMode === 'pos') {
            return 'filament.resources.sales.pages.pos-sale';
        }

        return parent::getView();
    }

    // ─── Redirect ─────────────────────────────────────────────────
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // ─── handleRecordCreation ─────────────────────────────────────
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {

            $items = $data['items'] ?? [];
            unset($data['items']);
            $items       = self::normalizeItems($items);
            $paymentDate = $data['payment_date'] ?? null;
            unset($data['payment_date']);

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

            $subtotal      = collect($items)->sum(fn ($i) => (float) ($i['line_total'] ?? 0));
            $totalDiscount = 0.0;
            $totalTax      = 0.0;

            foreach ($items as $item) {
                $lineTotal    = (float) ($item['line_total'] ?? 0);
                $discountRate = max(0, min(100, (float) ($item['discount'] ?? 0)));
                $taxRate      = max(0, min(100, (float) ($item['tax'] ?? 0)));

                $discountAmount = $lineTotal * ($discountRate / 100);
                $taxableAmount  = $lineTotal - $discountAmount;
                $taxAmount      = $taxableAmount * ($taxRate / 100);

                $totalDiscount += $discountAmount;
                $totalTax      += $taxAmount;
            }

            $data['subtotal']     = $subtotal;
            $data['total_amount'] = $subtotal - $totalDiscount + $totalTax;

            self::applyPaymentFields($data);

            $sale = static::getModel()::create($data);

            if ((float) ($data['paid_amount'] ?? 0) > 0) {
                PaymentLedgerService::recordSalePayment(
                    $sale,
                    (float) $data['paid_amount'],
                    $paymentDate ?? $data['sale_date'] ?? null
                );
            }

            foreach ($items as $item) {
                $branch = Branch::select('id', 'business_id')->find($item['branch_id']);
                if (! $branch) continue;

                $saleItem = $sale->items()->create([
                    'business_id' => $branch->business_id,
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

            $firstItem = $sale->items()->first();
            if ($firstItem) {
                \App\Models\Order::create([
                    'merchant_id' => $sale->merchant_id,
                    'sale_id'     => $sale->id,
                    'status'      => 'pending',
                ]);
            }

            $state = method_exists($this, 'form') ? $this->form->getRawState() : [];

            if (array_key_exists('merchant_logo', $state)) {
                $merchant = $user instanceof \App\Models\Merchant ? $user : $user?->merchant;
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
        $sale = $this->record->fresh(['customer']);
        if (! $sale) return;
        $this->queueSaleCreatedEmail($sale);
    }

    private function queueSaleCreatedEmail(Sale $sale): void
    {
        try {
            app(NotificationDispatcher::class)->dispatchSaleCreated($sale->fresh(['customer', 'merchant']));
        } catch (Throwable $exception) {
            Log::error('SaleCreated notification failed.', [
                'sale_id' => $sale->id,
                'error'   => $exception->getMessage(),
            ]);
        }
    }

    private static function normalizeItems(array $items): array
    {
        foreach ($items as &$item) {
            $qty          = (float) ($item['quantity'] ?? 0);
            $unitPrice    = (float) ($item['unit_price'] ?? 0);
            $lineSubtotal = $qty * $unitPrice;
            $lineTotal    = $lineSubtotal;

            $discountRate   = (float) ($item['discount'] ?? 0);
            $discountAmount = (float) ($item['discount_amount'] ?? 0);

            if ($discountAmount > 0 && $lineTotal > 0) {
                $discountRate = ($discountAmount / $lineTotal) * 100;
            }

            $discountRate   = max(0, min(100, $discountRate));
            $discountAmount = $lineTotal * ($discountRate / 100);

            $taxRate      = (float) ($item['tax'] ?? 0);
            $taxAmount    = (float) ($item['tax_amount'] ?? 0);
            $taxableAmount = $lineTotal - $discountAmount;

            if ($taxAmount > 0 && $taxableAmount > 0) {
                $taxRate = ($taxAmount / $taxableAmount) * 100;
            }

            $taxRate = max(0, min(100, $taxRate));

            $item['line_total'] = $lineTotal;
            $item['discount']   = round($discountRate, 6);
            $item['tax']        = round($taxRate, 6);
        }

        return $items;
    }

    private static function applyPaymentFields(array &$data): void
    {
        $totalAmount = max(0, (float) ($data['total_amount'] ?? 0));
        $paidAmount = $data['paid_amount'] ?? null;
        $paymentMethod = $data['payment_method'] ?? null;

        if ($paymentMethod === 'credit') {
            $paidAmount = max(0, (float) ($paidAmount ?? 0));
        } else {
            $paidAmount = ($paidAmount === null || $paidAmount === '' || (float) $paidAmount === 0.0)
                ? $totalAmount
                : (float) $paidAmount;
        }

        $paidAmount = max(0, min($totalAmount, (float) $paidAmount));
        $dueAmount = max(0, $totalAmount - $paidAmount);

        $data['paid_amount'] = round($paidAmount, 2);
        $data['due_amount'] = round($dueAmount, 2);
        $data['payment_type'] = $dueAmount > 0 ? 'credit' : 'cash';

        if ($dueAmount <= 0) {
            $data['due_date'] = null;
        } elseif (empty($data['due_date']) && ! empty($data['sale_date'])) {
            $data['due_date'] = \Carbon\Carbon::parse($data['sale_date'])->addDays(30)->toDateString();
        }

        unset($data['payment_method']);
    }
}