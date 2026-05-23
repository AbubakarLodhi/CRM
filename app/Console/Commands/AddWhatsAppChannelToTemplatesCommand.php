<?php

namespace App\Console\Commands;

use App\Models\NotificationTemplate;
use App\Support\NotificationTemplateChannels;
use Illuminate\Console\Command;

class AddWhatsAppChannelToTemplatesCommand extends Command
{
    protected $signature = 'notifications:add-whatsapp-channel';

    protected $description = 'Add WhatsApp to channels on all notification templates that currently include Email.';

    public function handle(): int
    {
        $updated = 0;

        NotificationTemplate::query()->each(function (NotificationTemplate $template) use (&$updated): void {
            $channels = NotificationTemplateChannels::normalize($template->channels);

            if (! in_array('email', $channels, true)) {
                return;
            }

            if (in_array('whatsapp', $channels, true)) {
                return;
            }

            $channels[] = 'whatsapp';
            $template->update(['channels' => NotificationTemplateChannels::normalize($channels)]);
            $updated++;
        });

        $this->info("Updated {$updated} template(s) to include WhatsApp alongside Email.");

        return self::SUCCESS;
    }
}
