<?php

namespace App\Filament\Resources\AssetTypes\Pages;

use App\Filament\Resources\AssetTypes\AssetTypeResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListAssetTypes extends ListRecords
{
    protected static string $resource = AssetTypeResource::class;

    protected function getHeaderActions(): array
    {
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        return [
            CreateAction::make()
                ->visible(fn () => auth($guard)->user()?->hasPermissionTo('asset_types.create', $guard)),
        ];
    }
}
