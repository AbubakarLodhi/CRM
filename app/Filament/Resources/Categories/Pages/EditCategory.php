<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use App\Enums\AttachmentType;
use App\Enums\AttachmentMetaType;


class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
    protected function getRedirectUrl(): string
    {
        $record = $this->getRecord();

        return static::getResource()::getUrl('index', [
            'parent_id' => $record->parent_id,
        ]);
    }


    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->icon) {
            // ✅ FileUpload EXPECTS ARRAY
            $data['category_icon'] = [$this->record->icon->photo_url];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $state = $this->form->getRawState();

        $path = collect($state['category_icon'] ?? null)->first();

        if (! $path) {
            return;
        }

        $this->record->icon()?->delete();

        $this->record->icon()->create([
            'merchant_id' => $this->record->merchant_id,
            'type'        => AttachmentType::IMAGE,
            'meta_type'   => AttachmentMetaType::CATEGORY_ICON,
            'photo_url'   => $path, // ✅ STRING
        ]);
    }



}
