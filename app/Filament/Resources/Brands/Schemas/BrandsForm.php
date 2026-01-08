<?php

namespace App\Filament\Resources\Brands\Schemas;

use App\Models\Category;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class BrandsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                       TextInput::make('name')
                           ->required()
                           ->maxLength(255),
                FileUpload::make('brand_logo')
                    ->label('Brand Logo')
                    ->image()
                    ->disk('public')
                    ->directory('brands/logos')
                    ->imagePreviewHeight(120)
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->dehydrated(false),

                Select::make('category_ids')
                    ->label('Categories')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->options(function () {
                        $user = Filament::auth()->user();

                        $merchantId = match (true) {
                            $user instanceof \App\Models\Merchant => $user->id,
                            $user instanceof \App\Models\User     => $user->merchant_id,
                            default                               => null,
                        };

                        return Category::query()
                            ->whereNotNull('parent_id')
                            ->when($merchantId, fn ($q) => $q->where('merchant_id', $merchantId))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    }),



                Hidden::make('merchant_id')
                    ->default(function () {
                        $user = Filament::auth()->user();

                        return match (true) {
                            $user instanceof \App\Models\Merchant => $user->id,
                            $user instanceof \App\Models\User     => $user->merchant_id,
                            default                                => null,
                        };
                    })
                    ->required(),


            ]);

    }
}
