<?php

namespace App\Filament\Resources\NotificationTemplates\Pages;

use App\Filament\Resources\NotificationTemplates\NotificationTemplateResource;
use App\Support\NotificationTemplateChannels;
use App\Support\NotificationTemplateEvents;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditNotificationTemplate extends EditRecord
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['events'] = NotificationTemplateEvents::normalize($data['events'] ?? $data['event'] ?? null);
        $data['channels'] = NotificationTemplateChannels::normalize($data['channels'] ?? $data['channel'] ?? null);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['events'] = NotificationTemplateEvents::normalize($data['events'] ?? null);
        $data['channels'] = NotificationTemplateChannels::normalize($data['channels'] ?? $data['channel'] ?? null);

        if (! NotificationTemplateChannels::includesEmail($data['channels'])) {
            $data['subject'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('notification_templates.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }

    protected function afterSave(): void
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

}
