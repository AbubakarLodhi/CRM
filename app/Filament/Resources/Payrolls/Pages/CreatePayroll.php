<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['allowances'] = $data['allowances'] ?? [];
        $data['deductions'] = $data['deductions'] ?? [];

        // Pre-fill user_id from query parameter if provided
        if (request()->has('user_id') && ! isset($data['user_id'])) {
            $data['user_id'] = request()->get('user_id');
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pre-fill user_id from query parameter if provided
        if (request()->has('user_id') && ! isset($data['user_id'])) {
            $data['user_id'] = request()->get('user_id');
        }

        return $data;
    }
}
