<?php

namespace App\Filament\Resources\Businesses\Pages;

use App\Filament\Resources\Businesses\BusinessResource;
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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('businesses.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
}
