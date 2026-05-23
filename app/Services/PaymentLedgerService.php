<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentLedgerService
{
    public static function displayablePayments(Sale|Purchase $document): \Illuminate\Support\Collection
    {
        $remainingDisplayAmount = max(0, (float) ($document->total_amount ?? 0));

        return $document->payments()
            ->orderBy('payment_date')
            ->orderBy('created_at')
            ->get(['id', 'payment_date', 'entry_type', 'amount', 'reference_no', 'method', 'created_at'])
            ->map(function (Payment $payment) use (&$remainingDisplayAmount): Payment {
                $amount = round((float) ($payment->amount ?? 0), 2);
                $displayAmount = $amount;

                if ($amount > 0) {
                    $displayAmount = min($amount, $remainingDisplayAmount);
                    $remainingDisplayAmount = max(0, round($remainingDisplayAmount - $displayAmount, 2));
                } elseif ($amount < 0) {
                    $remainingDisplayAmount = round($remainingDisplayAmount + abs($amount), 2);
                }

                $payment->setAttribute('display_amount', round($displayAmount, 2));

                return $payment;
            })
            ->filter(fn (Payment $payment): bool => round((float) $payment->getAttribute('display_amount'), 2) != 0.0)
            ->values();
    }

    public static function recordSalePayment(
        Sale $sale,
        float $amount,
        null|string|\DateTimeInterface $paymentDate = null,
        string $entryType = 'payment',
        ?string $notes = null,
        ?string $referenceNo = null,
        ?string $method = null,
    ): ?Payment {
        $amount = round($amount, 2);
        if ($amount == 0.0) {
            return null;
        }

        $payment = $sale->payments()->create([
            'merchant_id' => $sale->merchant_id,
            'party_type' => \App\Models\Customer::class,
            'party_id' => $sale->customer_id,
            'direction' => $amount > 0 ? 'in' : 'out',
            'entry_type' => $entryType,
            'amount' => $amount,
            'payment_date' => self::normalizeDate($paymentDate),
            'method' => filled($method) ? trim((string) $method) : null,
            'reference_no' => self::resolveReferenceNo($referenceNo, $sale),
            'notes' => $notes,
            'created_by' => self::resolveCreatorId($sale->created_by),
        ]);

        self::syncSaleTotals($sale->refresh());

        self::dispatchPaymentReceivedNotification($sale->refresh(), $payment);

        return $payment;
    }

    public static function recordPurchasePayment(
        Purchase $purchase,
        float $amount,
        null|string|\DateTimeInterface $paymentDate = null,
        string $entryType = 'payment',
        ?string $notes = null,
        ?string $referenceNo = null,
        ?string $method = null,
    ): ?Payment {
        $amount = round($amount, 2);
        if ($amount == 0.0) {
            return null;
        }

        $payment = $purchase->payments()->create([
            'merchant_id' => $purchase->merchant_id,
            'party_type' => \App\Models\Vendor::class,
            'party_id' => $purchase->vendor_id,
            'direction' => $amount > 0 ? 'out' : 'in',
            'entry_type' => $entryType,
            'amount' => $amount,
            'payment_date' => self::normalizeDate($paymentDate),
            'method' => filled($method) ? trim((string) $method) : null,
            'reference_no' => self::resolveReferenceNo($referenceNo, $purchase),
            'notes' => $notes,
            'created_by' => self::resolveCreatorId($purchase->created_by),
        ]);

        self::syncPurchaseTotals($purchase->refresh());

        self::dispatchPaymentReceivedNotification($purchase->refresh(), $payment);

        return $payment;
    }

    protected static function dispatchPaymentReceivedNotification(Sale|Purchase $document, Payment $payment): void
    {
        if ($payment->entry_type !== 'payment' || (float) $payment->amount <= 0) {
            return;
        }

        try {
            app(NotificationDispatcher::class)->dispatchPaymentReceived($document, $payment);
        } catch (\Throwable $exception) {
            Log::warning('payment_received notification failed', [
                'payment_id' => $payment->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public static function syncSaleTotals(Sale $sale): void
    {
        $totalAmount = max(0, (float) ($sale->total_amount ?? 0));
        $ledgerPaid = (float) $sale->payments()->sum('amount');
        $paidAmount = max(0, min($totalAmount, round($ledgerPaid, 2)));
        $dueAmount = round(max(0, $totalAmount - $paidAmount), 2);

        $sale->update([
            'paid_amount' => round($paidAmount, 2),
            'due_amount' => $dueAmount,
            'payment_type' => $dueAmount > 0 ? 'credit' : 'cash',
        ]);
    }

    public static function syncPurchaseTotals(Purchase $purchase): void
    {
        $totalAmount = max(0, (float) ($purchase->total_amount ?? 0));
        $ledgerPaid = (float) $purchase->payments()->sum('amount');
        $paidAmount = max(0, min($totalAmount, round($ledgerPaid, 2)));
        $dueAmount = round(max(0, $totalAmount - $paidAmount), 2);

        $purchase->update([
            'paid_amount' => round($paidAmount, 2),
            'due_amount' => $dueAmount,
            'payment_type' => $dueAmount > 0 ? 'credit' : 'cash',
        ]);
    }

    protected static function normalizeDate(null|string|\DateTimeInterface $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTime::createFromInterface($value))->toDateString();
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value)->toDateString();
        }

        return now()->toDateString();
    }

    protected static function resolveCreatorId(?string $fallbackId = null): ?string
    {
        $authUser = auth()->user();

        if ($authUser instanceof User) {
            return (string) $authUser->getKey();
        }

        if (filled($fallbackId) && User::query()->whereKey($fallbackId)->exists()) {
            return $fallbackId;
        }

        return null;
    }

    protected static function resolveReferenceNo(?string $referenceNo, Sale|Purchase $document): string
    {
        if (filled($referenceNo)) {
            return trim((string) $referenceNo);
        }

        $nextInstallment = (int) $document->payments()->count() + 1;

        if ($document instanceof Sale) {
            return sprintf('SALE-%s-PMT-%03d', (string) ($document->sale_no ?? $document->id), $nextInstallment);
        }

        return sprintf('PUR-%s-PMT-%03d', (string) ($document->purchase_no ?? $document->id), $nextInstallment);
    }
}
