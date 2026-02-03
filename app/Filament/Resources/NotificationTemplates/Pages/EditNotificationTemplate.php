<?php

namespace App\Filament\Resources\NotificationTemplates\Pages;

use App\Filament\Resources\NotificationTemplates\NotificationTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNotificationTemplate extends EditRecord
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if (! $this->record->is_active) {
            return;
        }

        \App\Models\NotificationTemplate::query()
            ->where('id', '!=', $this->record->id)
            ->where('event', $this->record->event)
            ->where('channel', $this->record->channel)
            ->where('merchant_id', $this->record->merchant_id)
            ->update(['is_active' => false]);
    }

}
