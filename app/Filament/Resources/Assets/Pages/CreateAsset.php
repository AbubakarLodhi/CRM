<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Assets\AssetResource;
use App\Models\Branch;
use App\Models\Merchant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        if ($user instanceof Merchant) {
            $data['merchant_id'] = $user->id;
            $data['created_by'] = null;
        } elseif ($user instanceof User) {
            $data['merchant_id'] = $user->merchant_id;
            $data['created_by'] = $user->id;
        }

        $data['business_id'] = Branch::query()
            ->whereKey($data['branch_id'])
            ->value('business_id');

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return static::getModel()::create($data);
    }

    protected function afterCreate(): void
    {
        $path = collect($this->form->getRawState()['attachment'] ?? null)->first();

        if ($path) {
            $this->record->attachment()?->delete();

            $this->record->attachment()->create([
                'merchant_id' => $this->record->merchant_id,
                'type' => $this->resolveAttachmentType((string) $path),
                'meta_type' => AttachmentMetaType::ASSET_FILE,
                'photo_url' => $path,
            ]);
        }

        Notification::make()
            ->title('Asset created')
            ->success()
            ->send();
    }

    private function resolveAttachmentType(string $path): AttachmentType
    {
        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
            ? AttachmentType::IMAGE
            : AttachmentType::FILE;
    }
}
