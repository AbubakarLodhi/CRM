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
        $user = Filament::auth()->user();

        $merchantId = match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };

        return ProductVariant::query()
            ->withoutTrashed()
            ->when(
                $merchantId,
                fn (Builder $query) => $query->where('merchant_id', $merchantId),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->when(
                request()->filled('product_id'),
                fn (Builder $query) =>
                $query->where('product_id', request('product_id'))
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
                ->color('default')
                ->label('Back')
                ->icon('heroicon-s-arrow-left')
                ->visible(fn () => request()->filled('product_id'))
                ->url(fn () =>
                ProductResource::getUrl('index', [
                    'brand_model_id' => request('brand_model_id'),
                    'brand_id'       => request('brand_id'),
                    'category_id'    => request('category_id'),
                ])
                ),
            CreateAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('products_variants.create', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
}
