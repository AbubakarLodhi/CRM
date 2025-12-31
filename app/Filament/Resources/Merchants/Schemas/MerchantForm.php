<?php

namespace App\Filament\Resources\Merchants\Schemas;

use App\Models\Merchant;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('password')
                    ->password()
                    ->required()
                    ->hiddenOn('edit'),
                TextInput::make('email')
                    ->email()
                    ->required(),
                FileUpload::make('merchant_logo')
                    ->label('Merchant Logo')
                    ->image()
                    ->disk('public')
                    ->directory('merchants/logos')
                    ->imagePreviewHeight(120)
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->dehydrated(false),

                TextInput::make('address_line_1')
                    ->required(),
                TextInput::make('address_line_2'),
                TextInput::make('city')
                    ->required(),
                TextInput::make('social_media_handles'),
                TextInput::make('website')
                    ->url(),
                Toggle::make('is_active')
                    ->required(),
                FileUpload::make('profile_photo')
                    ->label('Profile Photo')
                    ->image()
                    ->disk('public')
                    ->directory('merchants/profile-photos')
                    ->imagePreviewHeight('150')
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->saveUploadedFileUsing(fn ($file) =>
                    $file->store('merchants/profile-photos', 'public')
                    ),

                Select::make('status')
                    ->options([
                        Merchant::STATUS_PENDING => 'Pending',
                        Merchant::STATUS_VERIFIED => 'Verified',
                        Merchant::STATUS_REJECTED => 'Rejected',
                    ])
                    ->required()
                    ->default('pending'),
                Section::make('Role & Status')
                    ->schema([
                        Select::make('roles')
                            ->label('Roles')
                            ->multiple()
                            ->relationship('roles', 'name', fn ($query) => $query->where('guard_name', 'merchant'))
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
