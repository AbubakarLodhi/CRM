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
    $user = Filament::auth()->user();

    CustomerResource::syncCustomerBranches(
        $this->record,
        $this->branchIds,
        $user,
    );

    // ✅ Also attach user to those branches and their businesses
    if ($user instanceof \App\Models\User && ! empty($this->branchIds)) {
        $branches = \App\Models\Branch::whereIn('id', $this->branchIds)->get();

        foreach ($branches as $branch) {
            // Attach to business if not already
            $user->businesses()->syncWithoutDetaching([$branch->business_id]);

            // Attach to branch if not already
            $user->branches()->syncWithoutDetaching([$branch->id]);
        }
    }
}
}
