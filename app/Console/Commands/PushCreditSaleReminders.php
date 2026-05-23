<?php

namespace App\Console\Commands;

use App\Models\MerchantCreditReminderSetting;
use Illuminate\Console\Command;

class PushCreditSaleReminders extends Command
{
    protected $signature = 'credit-reminders:push
                            {--merchant= : Merchant UUID (optional; uses first merchant with reminders enabled)}';

    protected $description = 'Re-sync reminder dates and push due credit reminders (same as the Push reminders button)';

    public function handle(): int
    {
        $merchantId = $this->option('merchant')
            ?? MerchantCreditReminderSetting::query()
                ->where('is_enabled', true)
                ->value('merchant_id');

        if (! $merchantId) {
            $this->error('No merchant with reminders enabled. Pass --merchant=<uuid> or enable reminders in the app.');

            return self::FAILURE;
        }

        $this->line("Merchant: {$merchantId}");

        return $this->call('credit-reminders:send', ['--merchant' => $merchantId]);
    }
}
