<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        // Filter by user_id if provided in query parameter
        if (request()->filled('user_id')) {
            $query->where('user_id', request()->get('user_id'));
        }

        return $query;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
