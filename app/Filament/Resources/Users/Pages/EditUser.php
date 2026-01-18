<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use Illuminate\Support\Str;


class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        $name = (string) ($this->record?->name ?? '');

        return 'Edit ' . \Illuminate\Support\Str::limit($name, 30);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('users.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->profilePhoto) {
            // ✅ FileUpload expects ARRAY
            $data['profile_photo'] = [$this->record->profilePhoto->photo_url];
        }

        return $data;
    }


    protected function afterSave(): void
    {
        if ($this->record->status === User::STATUS_VERIFIED) {
            if (is_null($this->record->email_verified_at)) {
                $this->record->update([
                    'email_verified_at' => now(),
                ]);
            }
        } else {
            $this->record->update([
                'email_verified_at' => null,
            ]);
        }
        $data = $this->form->getState();

        // Sync businesses
        if (isset($data['businesses'])) {
            $syncData = [];

            foreach ($data['businesses'] as $businessId) {
                $syncData[$businessId] = ['id' => (string) Str::uuid()];
            }

            $this->record->businesses()->sync($syncData);
        }

        // Sync branches
        if (isset($data['branches'])) {
            $syncData = [];

            foreach ($data['branches'] as $branchId) {
                $syncData[$branchId] = ['id' => (string) Str::uuid()];
            }

            $this->record->branches()->sync($syncData);
        }

        // ✅ NORMALIZE profile photo
        $path = is_array($data['profile_photo'] ?? null)
            ? $data['profile_photo'][0]
            : $data['profile_photo'] ?? null;

        if ($path) {
            $this->record->profilePhoto()?->delete();

            $this->record->profilePhoto()->create([
                'merchant_id' => $this->record->merchant_id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::PROFILE_PHOTO,
                'photo_url'   => $path, // ✅ STRING
            ]);
        }
    }

}
