<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Filament\Resources\Activities\ActivityResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();

        $event      = Str::title((string) ($record->event ?? 'Activity'));
        $entityType = $record->auditable_type
            ? Str::headline(class_basename((string) $record->auditable_type))
            : null;

        return $entityType ? "{$event} — {$entityType}" : $event;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
