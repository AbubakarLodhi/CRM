<?php

namespace App\Filament\Resources\Branches\Schemas;

use App\Models\Admin;
use App\Models\Branch;
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
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('address')
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->tel(),
                Select::make('status')
                    ->options([
                        Branch::STATUS_PENDING => 'Pending',
                        Branch::STATUS_VERIFIED => 'Verified',
                        Branch::STATUS_REJECTED => 'Rejected',
                    ])
                    ->required()
                    ->default('pending'),
                Select::make('merchant_id')
                    ->label('Merchant')
                    ->relationship('merchant', 'name')
                    ->visible(fn() => Filament::auth()->user() instanceof Admin),

                Hidden::make('merchant_id')
                    ->default(fn() => Filament::auth()->user()?->id)
                    ->visible(fn() => !(Filament::auth()->user() instanceof Admin)),

                Select::make('business_id')
                    ->label('Business')
                    ->relationship(
                        name: 'business',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query) {
                            $user = Filament::auth()->user();

                            // Admin → see all businesses
                            if ($user instanceof Admin) {
                                return;
                            }

                            // Merchant → only their businesses
                            $query->where('merchant_id', $user->id);
                        }
                    )
                    ->preload()
                    ->searchable()
                    ->required(),
            ]);
    }
}
