<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Admin;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),

                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->unique(User::class, 'email')
                    ->required(),

                DateTimePicker::make('email_verified_at'),

                TextInput::make('password')
                    ->password()
                    ->required()
                    ->hiddenOn('edit'),

                Select::make('status')
                    ->options([
                        User::STATUS_PENDING => 'Pending',
                        User::STATUS_VERIFIED => 'Verified',
                        User::STATUS_REJECTED => 'Rejected',
                    ])
                    ->required()
                    ->preload()
                    ->searchable()
                    ->default('pending'),

                Select::make('merchant_id')
                    ->label('Merchant')
                    ->relationship('merchant', 'name')
                    ->visible(fn() => Filament::auth()->user() instanceof Admin),

                Hidden::make('merchant_id')
                    ->default(fn() => Filament::auth()->user()?->id)
                    ->visible(fn() => !(Filament::auth()->user() instanceof Admin)),

                Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->relationship('roles', 'name', fn($query) => $query->where('guard_name', 'staff'))
                    ->preload()
                    ->required(),

                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
