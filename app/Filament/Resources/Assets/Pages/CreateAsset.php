<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use App\Models\Branch;
use App\Models\Merchant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        if ($user instanceof Merchant) {
            $data['merchant_id'] = $user->id;
            $data['created_by'] = null;
        } elseif ($user instanceof User) {
            $data['merchant_id'] = $user->merchant_id;
            $data['created_by'] = $user->id;
        }

        $data['business_id'] = Branch::query()
            ->whereKey($data['branch_id'])
            ->value('business_id');

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return static::getModel()::create($data);
    }
}
