<?php

namespace App\Filament\Resources\MerchantSettings\Pages;

use App\Filament\Resources\MerchantSettings\MerchantSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMerchantSettings extends ListRecords
{
    protected static string $resource = MerchantSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
