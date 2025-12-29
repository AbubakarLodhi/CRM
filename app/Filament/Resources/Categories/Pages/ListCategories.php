<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function isSubCategoryContext(): bool
    {
        return request()->filled('parent_id');
    }

    // 🔧 MUST BE PUBLIC
    public function getTitle(): string
    {
        return $this->isSubCategoryContext()
            ? 'Sub-Categories'
            : 'Categories';
    }

    // 🔧 MUST BE PUBLIC
    public function getBreadcrumbs(): array
    {
        return [
            'Inventory',
            $this->isSubCategoryContext() ? 'Sub-Categories' : 'Categories',
        ];
    }

    protected function getTableQuery(): Builder
    {
        return Category::query()
            ->when(
                request('parent_id'),
                fn (Builder $q) => $q->where('parent_id', request('parent_id')),
                fn (Builder $q) => $q->whereNull('parent_id')
            );
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.delete', Filament::getCurrentPanel()->getAuthGuard())),

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
        ];
    }
}
