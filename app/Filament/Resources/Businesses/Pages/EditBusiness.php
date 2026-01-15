<?php

namespace App\Filament\Resources\Businesses\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Businesses\BusinessResource;
use App\Models\City;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditBusiness extends EditRecord
{
    protected static string $resource = BusinessResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * 🔁 Hydrate existing logo into form (SAME AS USER)
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->logo?->photo_url) {
            $data['business_logo'] = [
                $this->record->logo->photo_url, // relative path only
            ];
        } else {
            $data['business_logo'] = [];
        }



        return $data;
    }


    /**
     * 🔐 Prevent ownership tampering
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Filament::auth()->user();

        // Staff → cannot change ownership
        if ($user instanceof User) {
            unset($data['merchant_id']);
        }

        // Merchant → cannot reassign merchant
        if ($user instanceof \App\Models\Merchant) {
            unset($data['merchant_id']);
        }
        $this->validateCountriesHaveCities();
        return $data;
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

            $this->halt();
        }
    }

    /**
     * 💾 Save / replace business logo (SAME AS USER)
     */
    protected function afterSave(): void
    {
        $data = $this->form->getRawState();

        if (empty($data['business_logo']) || ! is_array($data['business_logo'])) {
            return;
        }

        // ✅ Extract FIRST VALUE from keyed array
        $path = collect($data['business_logo'])->first();

        if (! $path) {
            return;
        }

        // ✅ Do nothing if same image
        if ($this->record->logo?->photo_url === $path) {
            return;
        }

        $this->record->logo()?->delete();

        $this->record->logo()->create([
            'merchant_id' => $this->record->merchant_id,
            'type'        => AttachmentType::IMAGE,
            'meta_type'   => AttachmentMetaType::BUSINESS_LOGO,
            'photo_url'   => $path, // ✅ STRING ONLY
        ]);
    }



    /**
     * 🔐 Header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->visible(fn () =>
                auth(Filament::getCurrentPanel()->getAuthGuard())
                    ->user()
                    ?->hasPermissionTo(
                        'businesses.delete',
                        Filament::getCurrentPanel()->getAuthGuard()
                    )
                ),
        ];
    }
}
