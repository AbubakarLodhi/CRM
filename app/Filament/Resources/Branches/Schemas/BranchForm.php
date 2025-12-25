<?php

namespace App\Filament\Resources\Branches\Schemas;

use App\Models\Branch;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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
                    ->preload()
                    ->searchable()
                    ->required(),
                Select::make('business_id')
                    ->label('Business')
                    ->relationship('business', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),
            ]);
    }
}
