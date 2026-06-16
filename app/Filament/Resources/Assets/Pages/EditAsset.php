<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use App\Models\Branch;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['business_id'] = Branch::query()
            ->whereKey($data['branch_id'])
            ->value('business_id');

        return $data;
    }
}
