<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Console\Command;

class WhatsAppDiagnoseCommand extends Command
{
    protected $signature = 'whatsapp:diagnose';

    protected $description = 'Explain why WhatsApp messages may not arrive on phones.';

    public function handle(WhatsAppService $whatsApp): int
    {
        $driver = config('whatsapp.driver', 'log');

        $this->info('WhatsApp configuration');
        $this->table(
            ['Setting', 'Value'],
            [
                ['WHATSAPP_ENABLED', config('whatsapp.enabled') ? 'true' : 'false'],
                ['WHATSAPP_DRIVER', $driver],
                ['WHATSAPP_SENDER_PHONE', config('whatsapp.sender_phone')],
                ['WHATSAPP_PHONE_NUMBER_ID', filled(config('whatsapp.phone_number_id')) ? 'set' : 'MISSING'],
                ['WHATSAPP_ACCESS_TOKEN', filled(config('whatsapp.access_token')) ? 'set' : 'MISSING'],
                ['WHATSAPP_TEST_MODE', config('whatsapp.test_mode') ? 'true' : 'false'],
                ['WHATSAPP_TEST_PHONE', config('whatsapp.test_phone')],
            ],
        );

        $this->newLine();

        if ($whatsApp->usesLogDriver()) {
            $this->error('WHATSAPP_DRIVER=log');
            $this->line('Messages are only written to storage/logs/laravel.log.');
            $this->line('They are NOT sent to WhatsApp — that is why you receive email but not WhatsApp.');
            $this->newLine();
            $this->line('To deliver real WhatsApp messages:');
            $this->line('  1. Register sender ' . config('whatsapp.sender_phone') . ' at https://developers.facebook.com/ (WhatsApp → API Setup).');
            $this->line('  2. Copy Permanent access token → WHATSAPP_ACCESS_TOKEN');
            $this->line('  3. Copy Phone number ID (numeric) → WHATSAPP_PHONE_NUMBER_ID');
            $this->line('  4. In .env set: WHATSAPP_DRIVER=api');
            $this->line('  5. Run: php artisan config:clear');
            $this->line('  6. Test: php artisan whatsapp:test-notifications --event=credit_payment_reminder');

            return self::FAILURE;
        }

        if (! $whatsApp->canDeliverToPhones()) {
            $this->error('WHATSAPP_DRIVER=api but token or phone number ID is missing.');

            return self::FAILURE;
        }

        $this->info('Configuration looks ready for real WhatsApp delivery via Meta API.');

        if (config('whatsapp.test_mode')) {
            $this->warn('TEST MODE is ON — all messages go to: ' . config('whatsapp.test_phone'));
        }

        return self::SUCCESS;
    }
}
