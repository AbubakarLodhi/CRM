<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandsResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditBrands extends EditRecord
{
    protected static string $resource = BrandsResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
}
