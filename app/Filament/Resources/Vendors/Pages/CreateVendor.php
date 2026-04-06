<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Vendor;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $email = $data['email'] ?? null;

        if (! filled($email)) {
            return $data;
        }

        $normalizedEmail = mb_strtolower(trim((string) $email));

        $alreadyExists = Vendor::query()
            ->where('merchant_id', $data['merchant_id'])
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'data.email' => 'Vendor with this email already exists. Kindly use another email.',
            ]);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
