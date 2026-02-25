<?php

namespace App\Filament\Resources\InvoiceDynamicFields\Pages;

use App\Filament\Resources\InvoiceDynamicFields\InvoiceDynamicFieldResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceDynamicField extends EditRecord
{
    protected static string $resource = InvoiceDynamicFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['merchant_id'] = InvoiceDynamicFieldResource::resolveMerchantId();

        return $data;
    }
}
