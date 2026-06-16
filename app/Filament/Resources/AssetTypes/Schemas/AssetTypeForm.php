<?php

namespace App\Filament\Resources\AssetTypes\Schemas;

use App\Models\Merchant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AssetTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Type Name')
                ->required()
                ->maxLength(255),

            TextInput::make('code')
                ->label('Type Code')
                ->maxLength(50)
                ->nullable()
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null)
                ->helperText('Optional short code (e.g. IT, VEH, FURN).'),

            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->columnSpanFull(),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),

            Hidden::make('merchant_id')
                ->default(fn () => self::resolveMerchantId())
                ->required(),
        ]);
    }

    private static function resolveMerchantId(): ?string
    {
        $user = Filament::auth()->user();

        return match (true) {
            $user instanceof Merchant => $user->id,
            $user instanceof User => $user->merchant_id,
            default => null,
        };
    }
}
