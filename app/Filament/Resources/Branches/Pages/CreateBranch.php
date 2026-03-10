<?php

namespace App\Filament\Resources\Branches\Pages;

use App\Filament\Resources\Branches\BranchResource;
use App\Models\Branch;
use App\Models\City;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        if ($user instanceof \App\Models\Merchant) {
            $data['merchant_id'] = $user->id;
        }

        if ($user instanceof User) {
            $data['merchant_id'] = $user->merchant_id;
        }

        $this->validateCountriesHaveCities();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $existingBranch = Branch::withTrashed()
            ->where('name', $data['name'] ?? '')
            ->where('business_id', $data['business_id'] ?? null)
            ->whereNotNull('deleted_at')
            ->first();

        if ($existingBranch) {
            $existingBranch->restore();
            $existingBranch->fill($data);
            $existingBranch->save();

            Notification::make()
                ->title('Branch restored')
                ->body('A previously deleted branch with this name has been restored.')
                ->success()
                ->send();

            return $existingBranch;
        }

        return static::getModel()::create($data);
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

        if ($user instanceof User) {
            $this->record->users()->syncWithoutDetaching([$user->id]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
