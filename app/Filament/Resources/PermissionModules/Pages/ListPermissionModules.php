<?php

namespace App\Filament\Resources\PermissionModules\Pages;

use App\Filament\Resources\PermissionModules\PermissionModuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPermissionModules extends ListRecords
{
    protected static string $resource = PermissionModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
