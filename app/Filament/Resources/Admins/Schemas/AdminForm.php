<?php

namespace App\Filament\Resources\Admins\Schemas;

use App\Models\Admin;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class AdminForm
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
                    ->unique(Admin::class, 'email')
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->required()
                    ->hiddenOn('edit'),
                Toggle::make('status')
                    ->required(),
                Section::make('Role & Status')
                    ->schema([
                        Select::make('roles')
                            ->label('Roles')
                            ->multiple()
                            ->relationship('roles', 'name', fn ($query) => $query->where('guard_name', 'admin'))
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
