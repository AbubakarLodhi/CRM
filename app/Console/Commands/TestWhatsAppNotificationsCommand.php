<?php

namespace App\Console\Commands;

use App\Models\CreditReminder;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\Notifications\NotificationDispatcher;
use App\Support\NotificationTemplateEvents;
use App\Support\NotificationTemplateResolver;
use Illuminate\Console\Command;

class TestWhatsAppNotificationsCommand extends Command
{
    protected $signature = 'whatsapp:test-notifications
                            {--event= : Event to test: sale_created, purchase_created, payment_received, credit_payment_reminder, or all}
                            {--sale= : Sale ID for sale/credit/payment tests}
                            {--purchase= : Purchase ID for purchase/payment tests}';

    protected $description = 'Send test notifications (email + WhatsApp) using templates; WhatsApp uses test phone from config.';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $event = $this->option('event') ?: 'all';
        $events = $event === 'all'
            ? array_keys(NotificationTemplateEvents::builtinOptions())
            : [$event];

        $this->info('Sender (business): ' . config('whatsapp.sender_phone'));
        $this->info('WhatsApp test mode: ' . (config('whatsapp.test_mode') ? 'ON' : 'OFF'));
        $this->info('Test recipient: ' . config('whatsapp.test_phone'));
        $this->info('Driver: ' . config('whatsapp.driver') . (config('whatsapp.driver') === 'log' ? ' (messages are NOT sent to real phones)' : ''));
        $this->newLine();

        foreach ($events as $eventName) {
            $this->line("=== {$eventName} ===");

            match ($eventName) {
                'sale_created' => $this->testSaleCreated($dispatcher),
                'purchase_created' => $this->testPurchaseCreated($dispatcher),
                'payment_received' => $this->testPaymentReceived($dispatcher),
                'credit_payment_reminder' => $this->testCreditReminder($dispatcher),
                default => $this->warn("Unknown or custom event skipped: {$eventName}"),
            };

            $this->newLine();
        }

        $this->info('Done. Check storage/logs/laravel.log when driver=log.');

        return self::SUCCESS;
    }

    private function testSaleCreated(NotificationDispatcher $dispatcher): void
    {
        $sale = $this->resolveSale();

        if (! $sale) {
            return;
        }

        $result = $dispatcher->dispatchSaleCreated($sale);
        $this->reportResult($result);
    }

    private function testPurchaseCreated(NotificationDispatcher $dispatcher): void
    {
        $purchase = $this->resolvePurchase();

        if (! $purchase) {
            return;
        }

        $result = $dispatcher->dispatchPurchaseCreated($purchase);
        $this->reportResult($result);
    }

    private function testPaymentReceived(NotificationDispatcher $dispatcher): void
    {
        $sale = $this->resolveSale();

        if (! $sale) {
            return;
        }

        $payment = $sale->payments()->latest('created_at')->first();

        if (! $payment) {
            $this->warn('No payment on sale — record a payment first.');

            return;
        }

        $result = $dispatcher->dispatchPaymentReceived($sale, $payment);
        $this->reportResult($result);
    }

    private function testCreditReminder(NotificationDispatcher $dispatcher): void
    {
        $sale = $this->resolveSale();

        if (! $sale) {
            return;
        }

        $reminder = CreditReminder::query()
            ->where('sale_id', $sale->id)
            ->with('notificationTemplate', 'template')
            ->latest('updated_at')
            ->first();

        if (! $reminder) {
            $this->warn('No credit reminder for this sale.');

            return;
        }

        $template = $reminder->notificationTemplate
            ?? NotificationTemplateResolver::resolve('credit_payment_reminder', $sale->merchant_id);

        if (! $template) {
            $this->warn('No credit_payment_reminder template found.');

            return;
        }

        $this->line('Template channels: ' . implode(', ', $template->channels ?? []));

        $result = $dispatcher->dispatchCreditReminder(
            $sale,
            $reminder,
            $template,
            'customer',
            $sale->customer?->email,
            $sale->customer?->phone,
        );

        $this->reportResult($result);
    }

    private function resolveSale(): ?Sale
    {
        $id = $this->option('sale');

        $sale = $id
            ? Sale::query()->with(['customer', 'merchant'])->find($id)
            : Sale::query()->with(['customer', 'merchant'])->latest('created_at')->first();

        if (! $sale) {
            $this->warn('No sale found.');

            return null;
        }

        $this->line("Sale: {$sale->sale_no} ({$sale->id})");

        return $sale;
    }

    private function resolvePurchase(): ?Purchase
    {
        $id = $this->option('purchase');

        $purchase = $id
            ? Purchase::query()->with(['vendor', 'merchant'])->find($id)
            : Purchase::query()->with(['vendor', 'merchant'])->latest('created_at')->first();

        if (! $purchase) {
            $this->warn('No purchase found.');

            return null;
        }

        $this->line("Purchase: {$purchase->purchase_no} ({$purchase->id})");

        return $purchase;
    }

    private function reportResult(\App\Services\Notifications\NotificationDispatchResult $result): void
    {
        foreach ($result->sent as $line) {
            $this->info('Sent: ' . $line);
        }

        foreach ($result->skipped as $line) {
            $this->warn('Skipped: ' . $line);
        }

        if ($result->sent === [] && $result->skipped === []) {
            $this->warn('Nothing sent — ensure an active template exists for this event with email and/or WhatsApp channels.');
        }
    }
}
