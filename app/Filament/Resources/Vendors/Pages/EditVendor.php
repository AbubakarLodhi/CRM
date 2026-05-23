<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Purchase;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVendor extends EditRecord
{
    protected static string $resource = VendorResource::class;

    protected array $branchIds = [];

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['branch_ids'] = $this->record->branches()
            ->pluck('branches.id')
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->branchIds = array_values($data['branch_ids'] ?? []);
        unset($data['branch_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        VendorResource::syncVendorBranches(
            $this->record,
            $this->branchIds,
            Filament::auth()->user(),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->before(function (DeleteAction $action) {
                    $record = $this->record;

                    if (! $record) {
                        return;
                    }

                    $hasOutstandingCredit = Purchase::query()
                        ->withoutTrashed()
                        ->where('vendor_id', $record->id)
                        ->where('due_amount', '>', 0)
                        ->exists();

                    if ($hasOutstandingCredit) {
                        Notification::make()
                            ->title('Cannot delete vendor')
                            ->body('This vendor has outstanding credit purchases. Clear pending dues first.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                })
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('vendors.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
}
