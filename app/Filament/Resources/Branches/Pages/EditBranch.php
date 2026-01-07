<?php

namespace App\Filament\Resources\Branches\Pages;

use App\Filament\Resources\Branches\BranchResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditBranch extends EditRecord
{
    protected static string $resource = BranchResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * 🔐 Prevent merchant_id / business_id tampering
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Filament::auth()->user();

        // Staff cannot change ownership
        if ($user instanceof User) {
            unset($data['merchant_id'], $data['business_id']);
        }

        // Merchant cannot reassign merchant
        if ($user instanceof \App\Models\Merchant) {
            unset($data['merchant_id']);
        }

        return $data;
    }

    /**
     * 🔐 Header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('branches.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
}
