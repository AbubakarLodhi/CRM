<?php

namespace App\Filament\Resources\MerchantSettings\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
class MerchantSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            /* ================= MERCHANT LOGO ================= */
            FileUpload::make('merchant_logo')
                ->label('Merchant Logo')
                ->image()
                ->disk('public')
                ->directory('merchants/logos')
                ->imagePreviewHeight(120)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(2048)
                ->dehydrated(false)
                ->afterStateHydrated(fn ($component) => $component->state(null)),


        /* ================= PROFILE PHOTO ================= */
            FileUpload::make('profile_photo')
                ->label('Profile Photo')
                ->image()
                ->disk('public')
                ->directory('merchants/profile-photos')
                ->imagePreviewHeight(120)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(2048)
                ->dehydrated(false)
                ->afterStateHydrated(fn ($component) => $component->state(null)),

        /* ================= COLORS ================= */
            ColorPicker::make('primary_color')->required(),
            ColorPicker::make('secondary_color')->required(),

            TextInput::make('currency')->required()->default('USD'),
            TextInput::make('timezone')->required()->default('UTC'),

            /* ================= MERCHANT ================= */
            Hidden::make('merchant_id')
                ->default(fn () => auth('merchant')->id())
                ->required(),
        ]);
    }
}
