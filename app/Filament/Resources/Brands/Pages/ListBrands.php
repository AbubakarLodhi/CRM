<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandsResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Admin;
use App\Models\Brand;
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
            ->when(
                ! $user instanceof Admin,
                fn (Builder $query) => $query->where('merchant_id', $user->id)
            )
            ->when(
                request()->filled('category_id'),
                fn (Builder $query) =>
                $query->where('category_id', request('category_id'))
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
                ->url(fn () =>
                CategoryResource::getUrl('index', [
                    'parent_id' => request('category_id'),
                ])
                ),

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
