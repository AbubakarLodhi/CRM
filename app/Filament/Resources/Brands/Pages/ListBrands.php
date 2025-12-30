<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandsResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Admin;
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

        return Brand::query()
            // Merchant scoping
            ->when(
                ! $user instanceof Admin,
                fn (Builder $query) =>
                $query->where('merchant_id', $user->id)
            )

            // ✅ FIX: Filter via brand_category
            ->when(
                request()->filled('category_id'),
                fn (Builder $query) =>
                $query->whereExists(function ($sub) {
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
                        'categories.create',
                        Filament::getCurrentPanel()->getAuthGuard()
                    ))
        ];
    }
}
