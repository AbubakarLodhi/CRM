<?php

namespace App\Filament\Resources\Merchants\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Merchants\MerchantResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditMerchant extends EditRecord
{
    protected static string $resource = MerchantResource::class;

    public function getTitle(): string
    {
        $name = (string) ($this->record?->name ?? '');

        return 'Edit ' . \Illuminate\Support\Str::limit($name, 30);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('merchants.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
    protected function afterSave(): void
    {
        $state = $this->form->getRawState();

        /* ======================
         | PROFILE PHOTO
         |======================*/
        $profilePath = collect($state['profile_photo'] ?? null)->first();

        if ($profilePath) {
            $this->record->profilePhoto()?->delete();

            $this->record->profilePhoto()->create([
                'merchant_id' => $this->record->id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::PROFILE_PHOTO,
                'photo_url'   => $profilePath,
            ]);
        }

        /* ======================
         | MERCHANT LOGO
         |======================*/
        $logoPath = collect($state['merchant_logo'] ?? null)->first();

        if ($logoPath) {
            $this->record->logo()?->delete();

            $this->record->logo()->create([
                'merchant_id' => $this->record->id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::MERCHANT_LOGO,
                'photo_url'   => $logoPath,
            ]);
        }
    }



}
