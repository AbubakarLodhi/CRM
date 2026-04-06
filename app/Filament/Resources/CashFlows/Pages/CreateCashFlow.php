<?php

namespace App\Filament\Resources\CashFlows\Pages;

use App\Filament\Resources\CashFlows\CashFlowResource;
use App\Models\Customer;
use App\Models\Vendor;
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
        $data['direction'] = $this->resolveDirection($data['party_type'] ?? null, $data['flow_type'] ?? null);
        $data['reference_no'] = null;

        return $data;
    }

    private function resolveDirection(?string $partyType, ?string $flowType): string
    {
        if ($partyType === Customer::class) {
            return $flowType === 'loan' ? 'out' : 'in';
        }

        if ($partyType === Vendor::class) {
            return $flowType === 'loan' ? 'in' : 'out';
        }

        return 'in';
    }
}
