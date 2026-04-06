<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class CreditSettlementService
{
    public static function customerAvailableCredit(Sale $sale): float
    {
        $in = (float) CashFlow::query()
            ->where('merchant_id', $sale->merchant_id)
            ->where('party_type', Customer::class)
            ->where('party_id', $sale->customer_id)
            ->where('direction', 'in')
            ->sum('amount');

        $out = (float) CashFlow::query()
            ->where('merchant_id', $sale->merchant_id)
            ->where('party_type', Customer::class)
            ->where('party_id', $sale->customer_id)
            ->where('direction', 'out')
            ->sum('amount');

        return round(max(0, $in - $out), 2);
    }

    public static function vendorAvailableCredit(Purchase $purchase): float
    {
        $out = (float) CashFlow::query()
            ->where('merchant_id', $purchase->merchant_id)
            ->where('party_type', Vendor::class)
            ->where('party_id', $purchase->vendor_id)
            ->where('direction', 'out')
            ->sum('amount');

        $in = (float) CashFlow::query()
            ->where('merchant_id', $purchase->merchant_id)
            ->where('party_type', Vendor::class)
            ->where('party_id', $purchase->vendor_id)
            ->where('direction', 'in')
            ->sum('amount');

        return round(max(0, $out - $in), 2);
    }

    public static function settleSaleUsingCashFlow(Sale $sale, ?float $requestedAmount = null): float
    {
        return DB::transaction(function () use ($sale, $requestedAmount): float {
            $sale->refresh();

            $due = round(max(0, (float) ($sale->due_amount ?? 0)), 2);
            if ($due <= 0) {
                return 0.0;
            }

            $available = self::customerAvailableCredit($sale);
            if ($available <= 0) {
                return 0.0;
            }

            $requested = $requestedAmount !== null ? max(0, round($requestedAmount, 2)) : $available;
            $settlementAmount = round(min($due, $available, $requested), 2);

            if ($settlementAmount <= 0) {
                return 0.0;
            }

            PaymentLedgerService::recordSalePayment(
                $sale,
                $settlementAmount,
                now(),
                'adjustment',
                'Adjusted against customer cash flow balance',
                null,
                'Cash',
            );

            CashFlow::query()->create([
                'merchant_id' => $sale->merchant_id,
                'party_type' => Customer::class,
                'party_id' => $sale->customer_id,
                'flow_type' => 'advance',
                'direction' => 'out',
                'amount' => $settlementAmount,
                'flow_date' => now()->toDateString(),
                'reference_no' => 'CF-ADJ-SALE-' . (string) ($sale->sale_no ?? $sale->id),
                'notes' => 'Auto-adjusted against sale ' . (string) ($sale->sale_no ?? $sale->id),
                'created_by' => self::resolveCreatorId(),
            ]);

            return $settlementAmount;
        });
    }

    public static function settlePurchaseUsingCashFlow(Purchase $purchase, ?float $requestedAmount = null): float
    {
        return DB::transaction(function () use ($purchase, $requestedAmount): float {
            $purchase->refresh();

            $due = round(max(0, (float) ($purchase->due_amount ?? 0)), 2);
            if ($due <= 0) {
                return 0.0;
            }

            $available = self::vendorAvailableCredit($purchase);
            if ($available <= 0) {
                return 0.0;
            }

            $requested = $requestedAmount !== null ? max(0, round($requestedAmount, 2)) : $available;
            $settlementAmount = round(min($due, $available, $requested), 2);

            if ($settlementAmount <= 0) {
                return 0.0;
            }

            PaymentLedgerService::recordPurchasePayment(
                $purchase,
                $settlementAmount,
                now(),
                'adjustment',
                'Adjusted against vendor cash flow balance',
                null,
                'Cash',
            );

            CashFlow::query()->create([
                'merchant_id' => $purchase->merchant_id,
                'party_type' => Vendor::class,
                'party_id' => $purchase->vendor_id,
                'flow_type' => 'advance',
                'direction' => 'in',
                'amount' => $settlementAmount,
                'flow_date' => now()->toDateString(),
                'reference_no' => 'CF-ADJ-PUR-' . (string) ($purchase->purchase_no ?? $purchase->id),
                'notes' => 'Auto-adjusted against purchase ' . (string) ($purchase->purchase_no ?? $purchase->id),
                'created_by' => self::resolveCreatorId(),
            ]);

            return $settlementAmount;
        });
    }

    protected static function resolveCreatorId(): ?string
    {
        $user = auth()->user();

        if ($user instanceof User) {
            return (string) $user->getKey();
        }

        return null;
    }
}
