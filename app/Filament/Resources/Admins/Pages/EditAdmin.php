<?php

namespace App\Filament\Resources\Admins\Pages;

use App\Filament\Resources\Admins\AdminResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->visible(fn () => auth('admin')->user()?->hasPermissionTo('admins.delete', 'admin')),
        ];
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();

        if (empty($data['profile_photo'])) {
            return;
        }

        $this->record->profilePhoto()?->delete();

        $this->record->profilePhoto()->create([
            'merchant_id' => null,
            'type'        => AttachmentType::IMAGE,
            'meta_type'   => AttachmentMetaType::PROFILE_PHOTO,
            'photo_url'   => $data['profile_photo'],
        ]);
    }

}
