<?php

namespace App\Console\Commands;

use App\Services\CreditReminderScheduler;
use Illuminate\Console\Command;

class SendCreditSaleReminders extends Command
{
    protected $signature = 'credit-reminders:send
                            {--merchant= : Merchant UUID (optional; sends for all enabled merchants if omitted)}';

    protected $description = 'Send due credit sale payment reminder emails and print results to the terminal';

    public function handle(CreditReminderScheduler $scheduler): int
    {
        $merchantId = $this->option('merchant');

        if ($merchantId) {
            $result = $scheduler->pushDueRemindersForMerchant((string) $merchantId);
        } else {
            $result = $scheduler->processDueReminders();
        }

        $this->newLine();
        $this->info('=== Credit payment reminder results ===');
        $this->line('Queue driver: '.config('queue.default'));
        $this->line('Mailer: '.config('mail.default'));
        $this->line('Mail test mode: '.(config('mail.test_mode') ? 'ON' : 'OFF'));
        if (config('mail.test_mode')) {
            $this->line('Mail test recipient: '.config('mail.test_address'));
        }
        $this->newLine();

        if ($result['disabled']) {
            $this->warn($result['details'][0] ?? 'Reminders are disabled.');

            return self::SUCCESS;
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Sent', $result['sent']],
                ['Skipped (date rule not matched)', $result['skipped'] ?? 0],
                ['Not sent (error)', $result['failed']],
            ]
        );

        $this->newLine();
        $this->info('Details:');

        foreach ($result['details'] as $line) {
            if (str_starts_with($line, 'SENT')) {
                $this->line('<fg=green>'.$line.'</>');
            } elseif (str_starts_with($line, 'NOT SENT')) {
                $this->line('<fg=red>'.$line.'</>');
            } elseif (str_starts_with($line, 'SKIPPED')) {
                $this->line('<fg=yellow>'.$line.'</>');
            } elseif (str_starts_with($line, 'Waiting')) {
                $this->line('<fg=yellow>'.$line.'</>');
            } else {
                $this->line($line);
            }
        }

        $this->newLine();

        if ($result['sent'] > 0) {
            $this->comment('Emails were sent immediately via SMTP (not queued).');
        }

        if ($result['sent'] === 0 && $result['waiting'] === 0 && $result['failed'] === 0) {
            $this->comment('Nothing to process. Enable reminders, save templates, and ensure credit sales exist.');
        }

        return self::SUCCESS;
    }
}
