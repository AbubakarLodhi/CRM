<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class ViewAsset extends ViewRecord
{
    protected static string $resource = AssetResource::class;

    public function getTitle(): string
    {
        return 'View '.Str::limit((string) ($this->record?->name ?? ''), 40);
    }

    protected function getHeaderActions(): array
    {
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        return [
            Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-s-document-text')
                ->color('gray')
                ->url(fn (): string => route('assets.preview', ['id' => $this->record->id]))
                ->visible(fn () => auth($guard)->user()?->hasPermissionTo('assets.view', $guard)),

            EditAction::make()
                ->visible(fn () => auth($guard)->user()?->hasPermissionTo('assets.update', $guard)),
        ];
    }
}
