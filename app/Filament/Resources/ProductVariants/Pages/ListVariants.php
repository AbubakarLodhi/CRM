<?php

namespace App\Filament\Resources\ProductVariants\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\ProductVariants\ProductVariantResource;
use App\Models\ProductVariant;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVariants extends ListRecords
{
    protected static string $resource = ProductVariantResource::class;

    protected function getTableQuery(): Builder
    {
        return ProductVariant::query()
            ->when(
                request()->filled('product_id'),
                fn (Builder $q) =>
                $q->where('product_id', request('product_id'))
            );
    }

    public function getTitle(): string
    {
        return request()->filled('product_id')
            ? 'Product Variants'
            : 'All Variants';
    }

    protected function getHeaderActions(): array
    {
        return [

            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->visible(fn () => request()->filled('product_id'))
                ->url(fn () =>
                ProductResource::getUrl('index', [
                    'brand_model_id' => request('brand_model_id'),
                    'brand_id'       => request('brand_id'),
                    'category_id'    => request('category_id'),
                ])
                ),
            CreateAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.create', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
}
