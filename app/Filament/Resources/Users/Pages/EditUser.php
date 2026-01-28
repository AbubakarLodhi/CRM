<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Branch;
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
        $data = $this->form->getState();

        /** -------------------------------
         * Sync Branches
         * -------------------------------- */
        if (isset($data['branches'])) {
            $branchSync = [];

            foreach ($data['branches'] as $branchId) {
                $branchSync[$branchId] = ['id' => (string) Str::uuid()];
            }

            $this->record->branches()->sync($branchSync);

            /** -------------------------------
             * Resolve Businesses from Branches
             * -------------------------------- */
            $businessIds = Branch::whereIn('id', $data['branches'])
                ->pluck('business_id')
                ->unique()
                ->values();

            $businessSync = [];

            foreach ($businessIds as $businessId) {
                $businessSync[$businessId] = ['id' => (string) Str::uuid()];
            }

            $this->record->businesses()->sync($businessSync);
        }

        // Email verification logic remains unchanged
    }

}
