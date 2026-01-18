<?php

namespace App\Filament\Resources\Branches\Pages;

use App\Filament\Resources\Branches\BranchResource;
use App\Models\City;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBranch extends EditRecord
{
    protected static string $resource = BranchResource::class;

    public function getTitle(): string
    {
        $name = (string) ($this->record?->name ?? '');

        return 'Edit ' . \Illuminate\Support\Str::limit($name, 30);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * 🔐 Prevent merchant_id / business_id tampering
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Filament::auth()->user();

        // Staff cannot change ownership
        if ($user instanceof User) {
            unset($data['merchant_id'], $data['business_id']);
        }

        // Merchant cannot reassign merchant
        if ($user instanceof \App\Models\Merchant) {
            unset($data['merchant_id']);
        }
        $this->validateCountriesHaveCities();
        return $data;
    }

    /**
     * 🔐 Header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('branches.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
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
}
