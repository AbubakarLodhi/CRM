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
        $rows = [
            ['WHATSAPP_ENABLED', config('whatsapp.enabled') ? 'true' : 'false'],
            ['WHATSAPP_DRIVER', $driver],
            ['WHATSAPP_SENDER_PHONE', config('whatsapp.sender_phone')],
            ['WHATSAPP_TEST_MODE', config('whatsapp.test_mode') ? 'true' : 'false'],
            ['WHATSAPP_TEST_PHONE', config('whatsapp.test_phone')],
        ];

        if ($driver === 'api') {
            $rows[] = ['WHATSAPP_PHONE_NUMBER_ID', filled(config('whatsapp.phone_number_id')) ? 'set' : 'MISSING'];
            $rows[] = ['WHATSAPP_ACCESS_TOKEN', filled(config('whatsapp.access_token')) ? 'set' : 'MISSING'];
        }

        if ($driver === 'twilio') {
            $rows[] = ['TWILIO_SID', filled(config('whatsapp.twilio.sid')) ? 'set' : 'MISSING'];
            $rows[] = ['TWILIO_TOKEN', filled(config('whatsapp.twilio.token')) ? 'set' : 'MISSING'];
            $rows[] = ['TWILIO_WHATSAPP_FROM', config('whatsapp.twilio.whatsapp_from') ?: 'MISSING'];
        }

        $this->table(['Setting', 'Value'], $rows);
        $this->newLine();

        if ($whatsApp->usesLogDriver()) {
            $this->error('WHATSAPP_DRIVER=log — messages are only written to storage/logs/laravel.log.');

            return self::FAILURE;
        }

        if (! $whatsApp->canDeliverToPhones()) {
            $this->error("WHATSAPP_DRIVER={$driver} but required credentials are missing.");

            return self::FAILURE;
        }

        if ($driver === 'twilio') {
            $this->info('Twilio WhatsApp is configured.');
            $this->line('Sandbox: from your phone (+923461000454), send the join code to +14155238886 in WhatsApp first.');
            $this->line('See: https://console.twilio.com/us1/develop/sms/try-it-out/whatsapp-learn');
        } else {
            $this->info('Meta WhatsApp API is configured.');
        }

        if (config('whatsapp.test_mode')) {
            $this->warn('TEST MODE is ON — all CRM messages go to: ' . config('whatsapp.test_phone'));
        }

        return self::SUCCESS;
    }
}
