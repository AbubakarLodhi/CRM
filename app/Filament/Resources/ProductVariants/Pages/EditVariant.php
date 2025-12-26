<?php

namespace App\Filament\Resources\ProductVariants\Pages;

use App\Filament\Resources\ProductVariants\ProductVariantResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditVariant extends EditRecord
{
    protected static string $resource = ProductVariantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
}
