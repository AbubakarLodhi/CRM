<?php

namespace App\Filament\Resources\SaleReturns\Pages;

use App\Filament\Resources\SaleReturns\SaleReturnResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSaleReturn extends EditRecord
{
    protected static string $resource = SaleReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
