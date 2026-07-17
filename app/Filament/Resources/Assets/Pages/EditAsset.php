<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Assets\AssetResource;
use App\Models\Branch;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        return [
            ViewAction::make()
                ->visible(fn () => auth($guard)->user()?->hasPermissionTo('assets.view', $guard)),

            DeleteAction::make()
                ->visible(fn () => auth($guard)->user()?->hasPermissionTo('assets.delete', $guard)),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->attachment) {
            $data['attachment'] = [
                $this->record->attachment->photo_url,
            ];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['business_id'] = Branch::query()
            ->whereKey($data['branch_id'])
            ->value('business_id');

        return $data;
    }

    protected function afterSave(): void
    {
        $state = $this->form->getRawState();
        $path = collect($state['attachment'] ?? null)->first();

        if ($path) {
            $this->record->attachment()?->delete();

            $this->record->attachment()->create([
                'merchant_id' => $this->record->merchant_id,
                'type' => $this->resolveAttachmentType((string) $path),
                'meta_type' => AttachmentMetaType::ASSET_FILE,
                'photo_url' => $path,
            ]);

            return;
        }

        $this->record->attachment()?->delete();
    }

    private function resolveAttachmentType(string $path): AttachmentType
    {
        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
            ? AttachmentType::IMAGE
            : AttachmentType::FILE;
    }
}
