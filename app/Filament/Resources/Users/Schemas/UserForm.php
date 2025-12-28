<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
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
                FileUpload::make('profile_photo')
                    ->label('Profile Photo')
                    ->image()
                    ->disk('public')
                    ->directory('staff/profile-photos')
                    ->imagePreviewHeight(150)
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->saveUploadedFileUsing(fn ($file) =>
                    $file->store('staff/profile-photos', 'public')
                    ),
                Toggle::make('is_active')
                    ->required(),
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
                    ->preload()
                    ->searchable()
                    ->required(),
                Section::make('Role & Status')
                    ->schema([
                        Select::make('roles')
                            ->label('Roles')
                            ->multiple()
                            ->relationship('roles', 'name', fn ($query) => $query->where('guard_name', 'staff'))
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
