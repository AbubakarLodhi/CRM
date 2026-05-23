<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CreditPaymentReminderNotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $contentPath = resource_path('views/emails/templates/credit-payment-reminder-default.html');

        if (! is_readable($contentPath)) {
            $this->command?->warn('Credit payment reminder template file not found.');

            return;
        }

        $content = file_get_contents($contentPath);

        $existing = NotificationTemplate::query()
            ->whereNull('merchant_id')
            ->forEvent('credit_payment_reminder')
            ->first();

        $testPayload = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone_no' => '+92 300 1234567',
            'customer_reference' => 'CUST-001',
            'customer_address' => '123 Main Street, Lahore',
            'sale_no' => 'INV-2026-0001',
            'sale_date' => now()->subDays(10)->format('d/m/Y'),
            'due_date' => now()->addDays(5)->format('d/m/Y'),
            'remind_at' => now()->format('d/m/Y'),
            'reminder_name' => 'Before due date',
            'schedule_label' => 'Before due date',
            'payment_type_label' => 'Credit',
            'subtotal' => '50,000.00',
            'total_amount' => '52,500.00',
            'paid_amount' => '10,000.00',
            'due_amount' => '42,500.00',
            'merchant_name' => 'Your Business',
            'merchant_email' => 'billing@example.com',
            'merchant_phone_no' => '+92 42 1111111',
            'payment_history_html' => '<p style="margin:0;font-size:13px;color:#64748b;">Sample payment on ' . now()->subDays(3)->format('d/m/Y') . ' — PKR 10,000.00</p>',
            'invoice_html' => '<p style="margin:0;font-size:13px;color:#64748b;">Product line items appear here when sent with a real sale.</p>',
        ];

        $attributes = [
            'events' => ['credit_payment_reminder'],
            'channels' => ['email', 'whatsapp'],
            'subject' => 'Payment reminder — Invoice {{ $sale_no }}',
            'content' => $content,
            'is_active' => true,
            'meta' => [
                'test_payload' => json_encode($testPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ],
        ];

        if ($existing) {
            $existing->update($attributes);
            $this->command?->info('Updated system credit payment reminder notification template.');

            return;
        }

        NotificationTemplate::create(array_merge($attributes, [
            'id' => (string) Str::uuid(),
            'merchant_id' => null,
        ]));

        $this->command?->info('Created system credit payment reminder notification template.');
    }
}
