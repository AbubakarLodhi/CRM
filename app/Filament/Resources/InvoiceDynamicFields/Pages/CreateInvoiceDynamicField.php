<?php

namespace App\Filament\Resources\InvoiceDynamicFields\Pages;

use App\Filament\Resources\InvoiceDynamicFields\InvoiceDynamicFieldResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoiceDynamicField extends CreateRecord
{
    protected static string $resource = InvoiceDynamicFieldResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['merchant_id'] = InvoiceDynamicFieldResource::resolveMerchantId();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
