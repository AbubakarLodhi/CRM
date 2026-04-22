<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected array $branchIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->branchIds = array_values($data['branch_ids'] ?? []);
        unset($data['branch_ids']);

        $email = $data['email'] ?? null;

        if (! filled($email)) {
            return $data;
        }

        $normalizedEmail = mb_strtolower(trim((string) $email));

        $alreadyExists = Customer::query()
            ->where('merchant_id', $data['merchant_id'])
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'data.email' => 'Customer with this email already exists. Kindly use another email.',
            ]);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        CustomerResource::syncCustomerBranches(
            $this->record,
            $this->branchIds,
            Filament::auth()->user(),
        );
    }
}
