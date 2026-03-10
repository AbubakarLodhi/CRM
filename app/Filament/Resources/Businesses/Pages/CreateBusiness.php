<?php

namespace App\Filament\Resources\Businesses\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Businesses\BusinessResource;
use App\Models\Business;
use App\Models\City;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Nette\Schema\ValidationException;

class CreateBusiness extends CreateRecord
{
    protected static string $resource = BusinessResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateCountriesHaveCities();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $existingBusiness = Business::withTrashed()
            ->where('name', $data['name'] ?? '')
            ->where('merchant_id', $data['merchant_id'] ?? null)
            ->whereNotNull('deleted_at')
            ->first();

        if ($existingBusiness) {
            $existingBusiness->restore();
            $existingBusiness->fill($data);
            $existingBusiness->save();

            Notification::make()
                ->title('Business restored')
                ->body('A previously deleted business with this name has been restored.')
                ->success()
                ->send();

            return $existingBusiness;
        }

        return static::getModel()::create($data);
    }

    protected function validateCountriesHaveCities(): void
    {
        $data = $this->form->getRawState();
        $countries = $data['countries'] ?? [];
        $cities = $data['cities'] ?? [];

        if (empty($countries)) {
            return;
        }

        $cityCountryIds = City::whereIn('id', $cities)
            ->pluck('country_id')
            ->unique()
            ->toArray();

        $missingCountries = array_diff($countries, $cityCountryIds);

        if (! empty($missingCountries)) {

            // Optional: user-friendly notification
            Notification::make()
                ->title('Missing city selection')
                ->body('Please select at least one city for each selected country.')
                ->danger()
                ->send();

            // ⛔ THIS is what actually blocks save
            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();

        if (empty($data['business_logo']) || ! is_array($data['business_logo'])) {
            return;
        }

        // ✅ Always extract the FIRST VALUE, not index 0
        $path = collect($data['business_logo'])->first();

        if (! $path) {
            return;
        }

        $this->record->logo()?->delete();

        $this->record->logo()->create([
            'merchant_id' => $this->record->merchant_id,
            'type'        => AttachmentType::IMAGE,
            'meta_type'   => AttachmentMetaType::BUSINESS_LOGO,
            'photo_url'   => $path, // ✅ ALWAYS STRING
        ]);
    }

}
