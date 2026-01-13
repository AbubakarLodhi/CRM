<?php

namespace App\Filament\Resources\Businesses\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Businesses\BusinessResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditBusiness extends EditRecord
{
    protected static string $resource = BusinessResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * 🔁 Hydrate existing logo into form (SAME AS USER)
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->logo?->photo_url) {
            $data['business_logo'] = [
                $this->record->logo->photo_url, // relative path only
            ];
        } else {
            $data['business_logo'] = [];
        }



        return $data;
    }


    /**
     * 🔐 Prevent ownership tampering
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Filament::auth()->user();

        // Staff → cannot change ownership
        if ($user instanceof User) {
            unset($data['merchant_id']);
        }

        // Merchant → cannot reassign merchant
        if ($user instanceof \App\Models\Merchant) {
            unset($data['merchant_id']);
        }

        return $data;
    }

    /**
     * 💾 Save / replace business logo (SAME AS USER)
     */
    protected function afterSave(): void
    {
        $data = $this->form->getState();

        $path = is_array($data['business_logo'] ?? null)
            ? $data['business_logo'][0]
            : $data['business_logo'] ?? null;
        // If same image, do nothing
        if ($this->record->logo?->photo_url === $path) {
            return;
        }
        if ($path) {
            $this->record->logo()?->delete();

            $this->record->logo()->create([
                'merchant_id' => $this->record->merchant_id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::BUSINESS_LOGO,
                'photo_url'   => $path,
            ]);
        }
    }


    /**
     * 🔐 Header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->visible(fn () =>
                auth(Filament::getCurrentPanel()->getAuthGuard())
                    ->user()
                    ?->hasPermissionTo(
                        'businesses.delete',
                        Filament::getCurrentPanel()->getAuthGuard()
                    )
                ),
        ];
    }
}
