<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use App\Models\PermissionModule;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'id';

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        if (! $user) {
            return false;
        }

        // 🔐 Module gate
        if (! PermissionModule::isEnabledForCurrentMerchant('orders')) {
            return false;
        }

        // 🔐 Permission gate
        return $user->hasPermissionTo('orders.view', $guard);
    }


    public static function canCreate(): bool
    {
        return false; // Orders are created automatically from sales
    }

    public static function canEdit($record): bool
    {
        return false; // Orders are read-only
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user  = Filament::auth()->user();

        $merchantId = match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };

        if (! $merchantId) {
            return $query->whereRaw('1 = 0');
        }

        // 🟢 Merchant → all orders
        if ($user instanceof \App\Models\Merchant) {
            return $query->where('merchant_id', $merchantId);
        }

        // 🔵 Staff → pivot-restricted orders
        return $query
            ->where('merchant_id', $merchantId)
            ->whereHas('business.users', fn ($q) =>
            $q->where('users.id', $user->id)
            )
            ->whereHas('branch.users', fn ($q) =>
            $q->where('users.id', $user->id)
            );
    }


    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
