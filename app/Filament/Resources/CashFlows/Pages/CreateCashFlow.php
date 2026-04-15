<?php

namespace App\Filament\Resources\CashFlows\Pages;

use App\Filament\Resources\CashFlows\CashFlowResource;
use App\Models\CashFlow;
use Filament\Support\Enums\Width;
use Filament\Resources\Pages\CreateRecord;

class CreateCashFlow extends CreateRecord
{
    protected static string $resource = CashFlowResource::class;
    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['method'] = 'Cash';
        $data['direction'] = CashFlow::primaryDirectionForFlowType($data['flow_type'] ?? null);
        $data['reference_no'] = null;

        return $data;
    }
}
