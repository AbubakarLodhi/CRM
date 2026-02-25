<?php

namespace App\Filament\Resources\InvoiceDynamicFields\Pages;

use App\Filament\Resources\InvoiceDynamicFields\InvoiceDynamicFieldResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvoiceDynamicFields extends ListRecords
{
    protected static string $resource = InvoiceDynamicFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
