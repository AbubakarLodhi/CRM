<?php

namespace Database\Seeders;

use App\Enums\CreditReminderScheduleType;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CreditReminderProfessionalTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'file' => 'template_before_due_date_professional.html',
                'subject' => 'Payment reminder — Invoice {{ $sale_no }} due soon',
                'event' => 'credit_payment_reminder',
            ],
            [
                'file' => 'template_on_due_date_professional.html',
                'subject' => 'Payment due today — Invoice {{ $sale_no }}',
                'event' => 'credit_payment_reminder',
            ],
            [
                'file' => 'template_after_due_date_professional.html',
                'subject' => 'Overdue notice — Invoice {{ $sale_no }}',
                'event' => 'credit_payment_reminder',
            ],
        ];

        foreach ($templates as $meta) {
            $path = resource_path('views/emails/templates/' . $meta['file']);

            if (! is_readable($path)) {
                $this->command?->warn("Missing: {$meta['file']}");

                continue;
            }

            $content = file_get_contents($path);

            $existing = NotificationTemplate::query()
                ->whereNull('merchant_id')
                ->where('subject', $meta['subject'])
                ->first();

            $attributes = [
                'events' => [$meta['event']],
                'channels' => ['email', 'whatsapp'],
                'subject' => $meta['subject'],
                'content' => $content,
                'is_active' => true,
                'meta' => [
                    'template_file' => $meta['file'],
                    'schedule_hint' => match (true) {
                        str_contains($meta['file'], 'before') => CreditReminderScheduleType::BeforeDueDate->value,
                        str_contains($meta['file'], 'on_due') => CreditReminderScheduleType::OnDueDate->value,
                        str_contains($meta['file'], 'after') => CreditReminderScheduleType::AfterDueDate->value,
                        default => null,
                    },
                ],
            ];

            if ($existing) {
                $existing->update($attributes);
                $this->command?->info("Updated: {$meta['file']}");

                continue;
            }

            NotificationTemplate::create(array_merge($attributes, [
                'id' => (string) Str::uuid(),
                'merchant_id' => null,
            ]));

            $this->command?->info("Created: {$meta['file']}");
        }
    }
}
