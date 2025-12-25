<?php

namespace App\Filament\Resources\Merchants\Pages;

use App\Filament\Resources\Merchants\MerchantResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditMerchant extends EditRecord
{
    protected static string $resource = MerchantResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('merchants.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
}
