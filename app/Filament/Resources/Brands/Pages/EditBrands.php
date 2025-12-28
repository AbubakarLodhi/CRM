<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandsResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;


class EditBrands extends EditRecord
{
    protected static string $resource = BrandsResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }

    protected function afterSave(): void
    {
        $state = $this->form->getRawState();

        $path = collect($state['brand_logo'] ?? null)->first();

        if (! $path) {
            return;
        }

        $this->record->logo()?->delete();

        $this->record->logo()->create([
            'merchant_id' => $this->record->merchant_id,
            'type'        => AttachmentType::IMAGE,
            'meta_type'   => AttachmentMetaType::BRAND_LOGO,
            'photo_url'   => $path,
        ]);
    }




}
