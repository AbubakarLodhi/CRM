<?php

namespace App\Filament\Resources\Branches\Schemas;

use App\Models\Branch;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),

            Textarea::make('address')->columnSpanFull()->maxLength(400),

            TextInput::make('phone')->tel()->numeric()->minValue(0)->maxLength(15),

            Select::make('status')
                ->options([
                    Branch::STATUS_PENDING  => 'Pending',
                    Branch::STATUS_VERIFIED => 'Verified',
                    Branch::STATUS_REJECTED => 'Rejected',
                ])
                ->required()
                ->default(Branch::STATUS_PENDING)
                ->live()
                ->afterStateUpdated(function (callable $set, $state) {
                    if ($state === Branch::STATUS_VERIFIED) {
                        $set('is_active', true);
                    } else {
                        $set('is_active', false);
                    }
                }),

            Select::make('country_id')
                ->label('Country')
                ->relationship('country', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function (callable $set, $state, $old, $livewire) {
                    $set('city_id', null);
                    $livewire->resetValidation('data.country_id');
                    $livewire->resetErrorBag('data.country_id');
                }),

            Select::make('city_id')
                ->label('City')
                ->relationship(
                    'city',
                    'name',
                    fn ($query, callable $get) =>
                    $query->where('country_id', $get('country_id'))
                )
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                    $livewire->resetValidation('data.city_id');
                    $livewire->resetErrorBag('data.city_id');
                }),

            TextInput::make('postal_code')
                ->label('Postal Code')
                ->placeholder('e.g. 54000')
                ->numeric()
                ->minLength(5)
                ->minValue(0)
                ->rules(['digits_between:1,12'])
                ->maxLength(12)
                ->required()
                ,


            // ✅ Business scoped for Admin / Merchant / Staff
            Select::make('business_id')
                ->label('Business')
                ->relationship(
                    name: 'business',
                    titleAttribute: 'name',
                    modifyQueryUsing: function (Builder $query) {
                        $user = Filament::auth()->user();

                        // Merchant => only their businesses
                        if ($user instanceof \App\Models\Merchant) {
                            $query->where('merchant_id', $user->id);
                            return;
                        }

                        // Staff => only businesses assigned via pivot
                        if ($user instanceof User) {
                            $allowedBusinessIds = $user->businesses()->pluck('businesses.id')->toArray();

                            // No businesses assigned => show none
                            $query->whereIn('id', $allowedBusinessIds ?: ['00000000-0000-0000-0000-000000000000']);
                        }
                    }
                )
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                    $livewire->resetValidation('data.business_id');
                    $livewire->resetErrorBag('data.business_id');
                }),

            Toggle::make('is_active')
                ->label('Active')
                ->default(false)
                ->disabled(fn (callable $get) => $get('status') !== Branch::STATUS_VERIFIED)
                ->dehydrated(),
        ]);
    }
}
