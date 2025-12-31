<?php

namespace App\Filament\Resources\MerchantSettings\Pages;

use App\Filament\Resources\MerchantSettings\MerchantSettingResource;
use App\Models\MerchantSetting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMerchantSettings extends ListRecords
{
    protected static string $resource = MerchantSettingResource::class;

//    protected function getHeaderActions(): array
//    {
//        return [
//            CreateAction::make(),
//        ];
//    }

    public function mount(): void
    {
        if (auth('merchant')->check()) {
            $setting = MerchantSetting::where(
                'merchant_id',
                auth('merchant')->id()
            )->first();

            if ($setting) {
                redirect(
                    static::getResource()::getUrl('edit', [
                        'record' => $setting,
                        'panel' => 'merchant',
                    ])
                );
            }

            redirect(
                static::getResource()::getUrl('create', [
                    'panel' => 'merchant',
                ])
            );
        }

        parent::mount(); // admin flow
    }

    protected function getHeaderActions(): array
    {
        return auth('merchant')->check()
            ? []
            : [CreateAction::make()];
    }
}
