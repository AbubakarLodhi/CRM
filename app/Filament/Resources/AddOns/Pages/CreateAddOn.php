<?php

namespace App\Filament\Resources\AddOns\Pages;

use App\Filament\Resources\AddOns\AddOnResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAddOn extends CreateRecord
{
    protected static string $resource = AddOnResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
