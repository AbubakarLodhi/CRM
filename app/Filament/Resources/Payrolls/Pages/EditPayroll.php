<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditPayroll extends EditRecord
{
    protected static string $resource = PayrollResource::class;

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
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        return [
            ViewAction::make()
                ->visible(fn () => auth($guard)->user()?->hasPermissionTo('payrolls.view', $guard)),

            DeleteAction::make()
                ->color('danger')
                ->visible(fn () => auth($guard)->user()?->hasPermissionTo('payrolls.delete', $guard)),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['allowances'] = $data['allowances'] ?? [];
        $data['deductions'] = $data['deductions'] ?? [];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['allowances'] = $data['allowances'] ?? [];
        $data['deductions'] = $data['deductions'] ?? [];

        return $data;
    }
}
