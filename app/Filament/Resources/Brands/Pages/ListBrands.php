<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandsResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Brand;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBrands extends ListRecords
{
    protected static string $resource = BrandsResource::class;

    /**
     * ✅ Filter brands by sub-category (if provided)
     */
    protected function getTableQuery(): Builder
    {
        $user = Filament::auth()->user();

        $merchantId = match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };

        return Brand::query()
            ->when($merchantId, fn ($q) => $q->where('merchant_id', $merchantId))
            ->when(
                request()->filled('category_id'),
                fn ($q) =>
                $q->whereExists(function ($sub) {
                    $sub->selectRaw(1)
                        ->from('brand_category')
                        ->whereColumn('brand_category.brand_id', 'brands.id')
                        ->where('brand_category.category_id', request('category_id'));
                })
            );
    }

    /**
     * Optional: better title UX
     */
    public function getTitle(): string
    {
        return request()->filled('category_id')
            ? 'Brands'
            : 'All Brands';
    }

    protected function getHeaderActions(): array
    {
        return [
                Action::make('back')
                    ->color('default')
                    ->label('Back')
                    ->icon('heroicon-o-arrow-left')
                    ->visible(fn () => request()->filled('category_id'))
                    ->url(fn () => CategoryResource::getUrl('index', [
                        // 🔑 force sub-category context
                        'parent_id' => Category::query()
                            ->whereKey(request('category_id'))
                            ->value('parent_id'),
                    ])),

            CreateAction::make()
                ->visible(fn () =>
                auth(Filament::getCurrentPanel()->getAuthGuard())
                    ->user()?->hasPermissionTo(
                        'brands.create',
                        Filament::getCurrentPanel()->getAuthGuard()
                    ))
        ];
    }
}
