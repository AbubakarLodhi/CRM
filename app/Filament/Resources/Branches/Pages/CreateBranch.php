<?php

namespace App\Filament\Resources\Branches\Pages;

use App\Filament\Resources\Branches\BranchResource;
use App\Models\City;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        // Merchant creating branch
        if ($user instanceof \App\Models\Merchant) {
            $data['merchant_id'] = $user->id;
            return $data;
        }

        // Staff creating branch
        if ($user instanceof User) {
            $data['merchant_id'] = $user->merchant_id; // ✅ VALID merchant FK
            return $data;
        }

        $this->validateCountriesHaveCities();
        return $data;
    }

    protected function validateCountriesHaveCities(): void
    {
        $state = $this->form->getRawState();

        $countries = $state['countries'] ?? [];
        $cities = $state['cities'] ?? [];

        if (empty($countries)) {
            return;
        }

        $cityCountryIds = City::whereIn('id', $cities)
            ->pluck('country_id')
            ->unique()
            ->toArray();

        $missingCountries = array_diff($countries, $cityCountryIds);

        if (! empty($missingCountries)) {
            Notification::make()
                ->title('Missing city selection')
                ->body('Please select at least one city for each selected country.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $user = Filament::auth()->user();

        // Auto-assign branch to staff
        if ($user instanceof User) {
            $this->record->users()->syncWithoutDetaching([$user->id]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
