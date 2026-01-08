<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('products.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();

        if (empty($data['product_image'])) {
            return;
        }

        $this->record->productImage()?->delete();

        $this->record->productImage()->create([
            'merchant_id' => $this->record->merchant_id,
            'type'        => AttachmentType::IMAGE,
            'meta_type'   => AttachmentMetaType::PRODUCT_IMAGE,
            'photo_url'   => $data['product_image'],
        ]);

    }

}
