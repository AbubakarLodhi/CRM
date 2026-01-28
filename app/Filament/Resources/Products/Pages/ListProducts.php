<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\BrandModels\BrandModelResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getTableQuery(): Builder
    {
        $user = Filament::auth()->user();

        $merchantId = match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };

        return Product::query()
            ->when($merchantId, fn ($q) => $q->where('merchant_id', $merchantId))
            ->when(
                request()->filled('brand_model_id'),
                fn ($q) => $q->where('brand_model_id', request('brand_model_id'))
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
                ->color('default')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->visible(fn () => request()->filled('brand_model_id'))
                ->url(fn () =>
                BrandModelResource::getUrl('index', [
                    'brand_id'    => request('brand_id'),
                    'category_id' => request('category_id'),
                ])
                ),

            CreateAction::make()
                ->color('primary')
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('products.create', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
}
