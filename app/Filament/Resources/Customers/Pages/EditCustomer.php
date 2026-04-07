<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Sale;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    public function getTitle(): string
    {
        $name = (string) ($this->record?->name ?? '');

        return 'Edit ' . \Illuminate\Support\Str::limit($name, 30);
    }


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

                    $hasOutstandingCredit = Sale::query()
                        ->withoutTrashed()
                        ->where('customer_id', $record->id)
                        ->where('due_amount', '>', 0)
                        ->exists();

                    if ($hasOutstandingCredit) {
                        Notification::make()
                            ->title('Cannot delete customer')
                            ->body('This customer has outstanding credit sales. Clear pending dues first.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                })
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('customers.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
}
