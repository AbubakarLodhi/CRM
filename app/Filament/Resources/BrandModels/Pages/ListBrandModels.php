<?php

namespace App\Filament\Resources\BrandModels\Pages;

use App\Filament\Resources\BrandModels\BrandModelResource;
use App\Filament\Resources\Brands\BrandsResource;
use App\Models\Admin;
use App\Models\BrandModel;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBrandModels extends ListRecords
{
    protected static string $resource = BrandModelResource::class;

    /**
     * ✅ Filter models by brand when coming from Brands page
     */
    protected function getTableQuery(): Builder
    {
        $user = Filament::auth()->user();

        $merchantId = match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };

        return BrandModel::query()
            ->when($merchantId, fn ($q) => $q->where('merchant_id', $merchantId))
            ->when(
                request()->filled('brand_id'),
                fn ($q) => $q->where('brand_id', request('brand_id'))
            );
    }

    public function getTitle(): string
    {
        return request()->filled('brand_id')
            ? 'Models'
            : 'All Models';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->color('default')
                ->label('Back')
                ->icon('heroicon-s-arrow-left')
                ->visible(fn () => request()->filled('brand_id'))
                ->url(fn () =>
                BrandsResource::getUrl('index', [
                    'category_id' => request('category_id'),
                ])
                ),

            CreateAction::make()
                ->visible(fn () =>
                auth(Filament::getCurrentPanel()->getAuthGuard())
                    ->user()?->hasPermissionTo(
                        'models.create',
                        Filament::getCurrentPanel()->getAuthGuard()
                    )
                ),
        ];
    }
}
