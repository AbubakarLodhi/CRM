<?php

namespace App\Filament\Resources\MerchantSettings\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\MerchantSettings\MerchantSettingResource;
use App\Models\MerchantSetting;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateMerchantSetting extends CreateRecord
{
    protected static string $resource = MerchantSettingResource::class;
    protected static ?string $title = 'Merchant Settings';

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save changes')
                ->submit('create'),

            Action::make('cancel')
                ->label('Cancel')
                ->url($this->getResource()::getUrl('index')),
        ];
    }

    public function mount(): void
    {
        if (auth('merchant')->check()) {
            $existing = MerchantSetting::where(
                'merchant_id',
                auth('merchant')->id()
            )->first();

            if ($existing) {
                redirect(
                    static::getResource()::getUrl('edit', [
                        'record' => $existing,
                        'panel' => 'merchant',
                    ])
                );
            }
        }

        parent::mount();

        if (auth('merchant')->check()) {
            $merchant = auth('merchant')->user();
            $this->form->fill([
                'cash_in_hand' => $merchant->cash_in_hand,
                'cash_in_bank' => $merchant->cash_in_bank,
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth('merchant')->check()) {
            $data['merchant_id'] = auth('merchant')->id();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $state = $this->form->getRawState();
        $merchant = auth('merchant')->user();

//        dd($merchant);
        /* ===== MERCHANT LOGO ===== */
        if ($logo = collect($state['merchant_logo'] ?? null)->first()) {
            $merchant->logo()?->delete();

            $merchant->logo()->create([
                'merchant_id' => $merchant->id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::MERCHANT_LOGO,
                'photo_url'   => $logo,
            ]);

        }else{}

        if (array_key_exists('cash_in_hand', $state) || array_key_exists('cash_in_bank', $state)) {
            $merchant->update([
                'cash_in_hand' => $state['cash_in_hand'] ?? 0,
                'cash_in_bank' => $state['cash_in_bank'] ?? 0,
            ]);
        }

    }
}
