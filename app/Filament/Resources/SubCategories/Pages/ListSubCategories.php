<?php

namespace App\Filament\Resources\SubCategories\Pages;

use App\Filament\Resources\SubCategories\SubCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListSubCategories extends ListRecords
{
    protected static string $resource = SubCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.create', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
}
