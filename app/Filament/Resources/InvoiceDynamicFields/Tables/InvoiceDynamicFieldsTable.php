<?php

namespace App\Filament\Resources\InvoiceDynamicFields\Tables;

use App\Filament\Resources\InvoiceDynamicFields\InvoiceDynamicFieldResource;
use App\Models\InvoiceDynamicGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoiceDynamicFieldsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Template')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('section')
                    ->badge()
                    ->sortable(),

                TextColumn::make('fields_count')
                    ->label('Fields')
                    ->counts('fields')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('section')
                    ->options([
                        'header' => 'Header',
                        'footer' => 'Footer',
                    ]),
            ])
            ->recordUrl(fn (InvoiceDynamicGroup $record) =>
                auth(Filament::getCurrentPanel()->getAuthGuard())
                    ->user()
                    ?->hasPermissionTo('invoice_templates.update', Filament::getCurrentPanel()->getAuthGuard())
                    ? InvoiceDynamicFieldResource::getUrl('edit', [
                        'record' => $record,
                    ])
                    : null
            )
            ->recordActions([
                EditAction::make()
                    ->label('Edit Template')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('invoice_templates.update', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('invoice_templates.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ])
            ->defaultSort('section');
    }
}
