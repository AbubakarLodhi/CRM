<?php

namespace App\Filament\Resources\AddOns\Pages;

use App\Filament\Resources\AddOns\AddOnResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditAddOn extends EditRecord
{
    protected static string $resource = AddOnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
