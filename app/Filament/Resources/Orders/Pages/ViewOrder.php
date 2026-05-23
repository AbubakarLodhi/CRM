<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        $name = (string) ($this->record?->name ?? '');

        return 'View ' . \Illuminate\Support\Str::limit($name, 30);
    }


    protected function getHeaderActions(): array
    {
        return [
            // Orders are read-only, actions will be added later
        ];
    }
}
