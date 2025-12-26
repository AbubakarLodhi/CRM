<?php

namespace App\Filament\Resources\BrandModels\Pages;

use App\Filament\Resources\BrandModels\BrandModelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBrandModel extends CreateRecord
{
    protected static string $resource = BrandModelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
