<?php

namespace App\Filament\Resources\InvoiceDynamicFields;

use App\Filament\Resources\InvoiceDynamicFields\Pages\CreateInvoiceDynamicField;
use App\Filament\Resources\InvoiceDynamicFields\Pages\EditInvoiceDynamicField;
use App\Filament\Resources\InvoiceDynamicFields\Pages\ListInvoiceDynamicFields;
use App\Filament\Resources\InvoiceDynamicFields\Schemas\InvoiceDynamicFieldForm;
use App\Filament\Resources\InvoiceDynamicFields\Tables\InvoiceDynamicFieldsTable;
use App\Models\InvoiceDynamicGroup;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceDynamicFieldResource extends Resource
{
    protected static ?string $model = InvoiceDynamicGroup::class;
    protected static ?string $slug = 'invoice-templates';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $navigationLabel = 'Invoice Templates';
    protected static ?string $modelLabel = 'Invoice Template';
    protected static ?string $pluralModelLabel = 'Invoice Templates';
    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'merchant';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        if ($user instanceof \App\Models\Merchant) {
            return $query->where('merchant_id', $user->id);
        }

        if ($user instanceof \App\Models\User && $user->merchant_id) {
            return $query->where('merchant_id', $user->merchant_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return InvoiceDynamicFieldForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoiceDynamicFieldsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoiceDynamicFields::route('/'),
            'create' => CreateInvoiceDynamicField::route('/create'),
            'edit' => EditInvoiceDynamicField::route('/{record}/edit'),
        ];
    }

    public static function resolveMerchantId(): ?string
    {
        $user = Filament::auth()->user();

        if ($user instanceof \App\Models\Merchant) {
            return $user->id;
        }

        if ($user instanceof \App\Models\User) {
            return $user->merchant_id;
        }

        return null;
    }
}
