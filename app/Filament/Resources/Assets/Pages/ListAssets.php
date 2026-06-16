<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use App\Models\AssetType;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    public function getTitle(): string
    {
        if (request()->filled('asset_type_id')) {
            $typeName = AssetType::query()->whereKey(request('asset_type_id'))->value('name');

            if ($typeName) {
                return 'Assets: '.$typeName;
            }
        }

        return parent::getTitle();
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if (request()->filled('asset_type_id')) {
            $query->where('asset_type_id', request('asset_type_id'));
        }

        return $query;
    }

    protected function getHeaderActions(): array
    {
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        return [
            CreateAction::make()
                ->visible(fn () => auth($guard)->user()?->hasPermissionTo('assets.create', $guard)),
        ];
    }
}
