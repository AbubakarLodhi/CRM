<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Admin;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function isSubCategoryContext(): bool
    {
        return request()->filled('parent_id');
    }

    public function getTitle(): string
    {
        return $this->isSubCategoryContext() ? 'Sub-Categories' : 'Categories';
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Inventory',
            $this->isSubCategoryContext() ? 'Sub-Categories' : 'Categories',
        ];
    }

    /**
     * ✅ Table view query (what rows appear)
     */
    protected function getTableQuery(): Builder
    {
        $user = Filament::auth()->user();

        return Category::query()
            ->when(
                ! $user instanceof Admin,
                fn (Builder $query) => $query->where('merchant_id', $user->id)
            )
            ->when(
                request()->filled('parent_id'),
                fn (Builder $query) =>
                $query->where('parent_id', request('parent_id')),
                fn (Builder $query) =>
                $query->whereNull('parent_id')
            );
    }

    /**
     * ✅ CRITICAL: Action record resolver (what Filament uses to resolve record for Delete/Edit actions)
     * This must ignore your table query constraints.
     */
    protected function resolveTableRecord(?string $key): ?Model
    {
        if (! $key) {
            return null;
        }

        return Category::query()->whereKey($key)->first();
    }

    protected function getHeaderActions(): array
    {
        return [
            ...(
            $this->isSubCategoryContext()
                ? [
                Action::make('back')
                    ->label('Back')
                    ->icon('heroicon-o-arrow-left')
                    ->url(static::getResource()::getUrl()),
            ]
                : []
            ),
            CreateAction::make()
                ->visible(fn () =>
                auth(Filament::getCurrentPanel()->getAuthGuard())
                    ->user()
                    ?->hasPermissionTo('categories.delete', Filament::getCurrentPanel()->getAuthGuard())
                ),
            ];
    }
}
