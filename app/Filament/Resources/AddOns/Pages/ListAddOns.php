<?php

namespace App\Filament\Resources\AddOns\Pages;

use App\Filament\Resources\AddOns\AddOnResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListAddOns extends ListRecords
{
    protected static string $resource = AddOnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.create', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
}
