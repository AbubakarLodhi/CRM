<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBrands extends CreateRecord
{
    protected static string $resource = BrandsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
