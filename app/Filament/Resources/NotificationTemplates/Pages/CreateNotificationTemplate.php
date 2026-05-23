<?php

namespace App\Filament\Resources\NotificationTemplates\Pages;

use App\Filament\Resources\NotificationTemplates\NotificationTemplateResource;
use App\Support\NotificationTemplateChannels;
use App\Support\NotificationTemplateEvents;
use Filament\Resources\Pages\CreateRecord;

class CreateNotificationTemplate extends CreateRecord
{
    protected static string $resource = NotificationTemplateResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        $data['merchant_id'] = match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };

        $data['events'] = NotificationTemplateEvents::normalize($data['events'] ?? null);
        $data['channels'] = NotificationTemplateChannels::normalize($data['channels'] ?? $data['channel'] ?? null);

        if (! NotificationTemplateChannels::includesEmail($data['channels'])) {
            $data['subject'] = null;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->record->is_active) {
            return;
        }

        foreach ($this->record->events ?? [] as $event) {
            foreach ($this->record->channels ?? [] as $channel) {
                \App\Models\NotificationTemplate::query()
                    ->where('id', '!=', $this->record->id)
                    ->forEvent($event)
                    ->forChannel($channel)
                    ->where('merchant_id', $this->record->merchant_id)
                    ->update(['is_active' => false]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
