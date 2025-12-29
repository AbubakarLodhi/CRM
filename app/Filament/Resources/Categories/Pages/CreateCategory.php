<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;
use App\Enums\AttachmentType;
use App\Enums\AttachmentMetaType;


class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index', request()->only('parent_id'));
    }


    protected function afterCreate(): void
    {
        $state = $this->form->getRawState();

        $path = collect($state['category_icon'] ?? null)->first();

        if (! $path) {
            return;
        }

        $this->record->icon()->create([
            'merchant_id' => $this->record->merchant_id,
            'type'        => AttachmentType::IMAGE,
            'meta_type'   => AttachmentMetaType::CATEGORY_ICON,
            'photo_url'   => $path,
        ]);
    }


}
