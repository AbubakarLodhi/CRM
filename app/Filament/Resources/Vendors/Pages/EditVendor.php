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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
