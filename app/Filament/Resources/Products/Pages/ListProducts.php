<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\BrandModels\BrandModelResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getTableQuery(): Builder
    {
        return Product::query()
            ->when(
                request()->filled('brand_model_id'),
                fn (Builder $q) =>
                $q->where('brand_model_id', request('brand_model_id'))
            );
    }

    public function getTitle(): string
    {
        return request()->filled('brand_model_id')
            ? 'Products'
            : 'All Products';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->visible(fn () => request()->filled('brand_model_id'))
                ->url(fn () =>
                BrandModelResource::getUrl('index', [
                    'brand_id'    => request('brand_id'),
                    'category_id' => request('category_id'),
                ])
                ),

            CreateAction::make(),
        ];
    }
}
