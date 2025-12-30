<?php

namespace App\Filament\Resources\Brands\Schemas;

use App\Models\Admin;
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

                        return Category::query()
                            // ✅ Only sub-categories
                            ->whereNotNull('parent_id')

                            // ✅ Merchant scoping
                            ->when(
                                ! $user instanceof Admin,
                                fn ($q) => $q->where(
                                    'merchant_id',
                                    $user->merchant_id ?? $user->id
                                )
                            )

                            // (Optional) Admin sees all merchants’ categories
                            ->when(
                                $user instanceof Admin,
                                fn ($q) => $q
                            )

                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    }),



        Hidden::make('merchant_id')
                    ->default(fn () => Filament::auth()->id())
                    ->required(),

                   ]);

    }
}
