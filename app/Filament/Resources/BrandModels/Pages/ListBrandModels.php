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

        return BrandModel::query()
            ->when(
                ! $user instanceof Admin,
                fn (Builder $query) => $query->where('merchant_id', $user->id)
            )
            ->when(
                request()->filled('brand_id'),
                fn (Builder $query) =>
                $query->where('brand_id', request('brand_id'))
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
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
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
                        'categories.create',
                        Filament::getCurrentPanel()->getAuthGuard()
                    )
                ),
        ];
    }
}
