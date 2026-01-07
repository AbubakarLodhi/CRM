<?php

namespace App\Filament\Resources\Branches\Schemas;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),

            Textarea::make('address')->columnSpanFull(),

            TextInput::make('phone')->tel(),

            Select::make('status')
                ->options([
                    Branch::STATUS_PENDING => 'Pending',
                    Branch::STATUS_VERIFIED => 'Verified',
                    Branch::STATUS_REJECTED => 'Rejected',
                ])
                ->required()
                ->default(Branch::STATUS_PENDING),

            Select::make('country_id')
                ->label('Country')
                ->relationship('country', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('city_id', null)),

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
                ->required(),

            TextInput::make('postal_code')
                ->label('Postal Code')
                ->placeholder('e.g. 54000')
                ->maxLength(12)
                ->required(),
            // ✅ Admin chooses merchant
            Select::make('merchant_id')
                ->label('Merchant')
                ->relationship('merchant', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn () => Filament::auth()->user() instanceof Admin),


            // ✅ Business scoped for Admin / Merchant / Staff
            Select::make('business_id')
                ->label('Business')
                ->relationship(
                    name: 'business',
                    titleAttribute: 'name',
                    modifyQueryUsing: function (Builder $query) {
                        $user = Filament::auth()->user();

                        // Admin => all businesses
                        if ($user instanceof Admin) {
                            return;
                        }

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
                ->required(),
        ]);
    }
}
