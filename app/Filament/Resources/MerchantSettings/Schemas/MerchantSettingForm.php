<?php

namespace App\Filament\Resources\MerchantSettings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchantSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([


            FileUpload::make('merchant_logo')
                ->label('Merchant Logo')
                ->image()
                ->disk('public')
                ->directory('merchants/logos')
                ->imagePreviewHeight(120),
            
            FileUpload::make('profile_photo')
                ->label('Profile Photo')
                ->image()
                ->disk('public')
                ->directory('merchants/profile-photos')
                ->imagePreviewHeight(120),


            /* ================= LIGHT MODE COLORS ================= */
            Section::make('Light Mode Colors')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    ColorPicker::make('primary_color')
                        ->label('Primary')
                        ->required(),

                    ColorPicker::make('secondary_color')
                        ->label('Secondary')
                        ->required(),

                    ColorPicker::make('warning_color')
                        ->label('Warning')
                        ->required(),

                    ColorPicker::make('danger_color')
                        ->label('Danger')
                        ->required(),

                    ColorPicker::make('success_color')
                        ->label('Success')
                        ->required(),

                    ColorPicker::make('default_color')
                        ->label('Default')
                        ->required(),
                ]),

            /* ================= MERCHANT ================= */
            Hidden::make('merchant_id')
                ->default(fn() => auth('merchant')->id())
                ->required(),
        ]);
    }
}
