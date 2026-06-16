<?php

namespace App\Filament\Resources\AssetTypes\Pages;

use App\Filament\Resources\AssetTypes\AssetTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditAssetType extends EditRecord
{
    protected static string $resource = AssetTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        return [
            DeleteAction::make()
                ->visible(fn () => auth($guard)->user()?->hasPermissionTo('asset_types.delete', $guard)),
        ];
    }
}
