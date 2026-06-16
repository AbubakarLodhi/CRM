<?php

namespace App\Filament\Resources\CashFlows;

use App\Filament\Resources\CashFlows\Pages\CreateCashFlow;
use App\Filament\Resources\CashFlows\Pages\EditCashFlow;
use App\Filament\Resources\CashFlows\Pages\ListCashFlows;
use App\Filament\Resources\CashFlows\Pages\ViewPartyCashFlows;
use App\Filament\Resources\CashFlows\Schemas\CashFlowForm;
use App\Filament\Resources\CashFlows\Tables\CashFlowsTable;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Branch;
use App\Models\Business;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\PermissionModule;
use App\Models\User;
use App\Models\Vendor;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class CashFlowResource extends Resource
{
    protected static ?string $model = CashFlow::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'reference_no';

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        if (! $user) {
            return false;
        }

        if (! PermissionModule::isEnabledForCurrentMerchant('cash_flows')) {
            return false;
        }

        return $user->hasPermissionTo('cash_flows.view', $guard);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        $merchantId = static::merchantIdFor($user);

        $query = CashFlow::query()
            ->withoutTrashed()
            ->when(
                filled($merchantId),
                fn (Builder $q) => $q->where('merchant_id', $merchantId),
                fn (Builder $q) => $q->whereRaw('1 = 0'),
            );

        if ($user instanceof User) {
            $businessIds = $user->businesses()->pluck('businesses.id');
            $branchIds = $user->branches()->pluck('branches.id');

            if ($businessIds->isEmpty() || $branchIds->isEmpty()) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereIn('business_id', $businessIds)
                ->whereIn('branch_id', $branchIds);
        }

        return $query;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();
        $merchantId = static::merchantIdFor($user);

        if (! $merchantId) {
            return $query->whereRaw('1 = 0');
        }

        $query = $query
            ->where('merchant_id', $merchantId)
            ->whereNull('settlement_for_id');

        if ($user instanceof User) {
            $businessIds = $user->businesses()->pluck('businesses.id');
            $branchIds = $user->branches()->pluck('branches.id');

            if ($businessIds->isEmpty() || $branchIds->isEmpty()) {
                return $query->whereRaw('1 = 0');
            }

            $query
                ->whereIn('business_id', $businessIds)
                ->whereIn('branch_id', $branchIds);
        }

        $rankedPerParty = (clone $query)
            ->selectRaw('id, ROW_NUMBER() OVER (PARTITION BY party_type, party_id ORDER BY flow_date DESC, created_at DESC) AS cf_row_num');

        return CashFlow::query()
            ->whereIn('id', function (QueryBuilder $subquery) use ($rankedPerParty): void {
                $subquery
                    ->fromSub($rankedPerParty, 'ranked_cash_flows')
                    ->where('ranked_cash_flows.cf_row_num', 1)
                    ->select('ranked_cash_flows.id');
            });
    }

    public static function form(Schema $schema): Schema
    {
        return CashFlowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashFlowsTable::configureGrouped($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashFlows::route('/'),
            'create' => CreateCashFlow::route('/create'),
            'party' => ViewPartyCashFlows::route('/party/{partyType}/{partyId}'),
            'edit' => EditCashFlow::route('/{record}/edit'),
        ];
    }

    public static function merchantIdFor(Merchant|User|null $user): ?string
    {
        return match (true) {
            $user instanceof Merchant => $user->id,
            $user instanceof User => $user->merchant_id,
            default => null,
        };
    }

    public static function accessibleBusinessesQuery(Merchant|User|null $user): Builder
    {
        $merchantId = static::merchantIdFor($user);

        $query = Business::query()
            ->withoutTrashed()
            ->when(
                filled($merchantId),
                fn (Builder $query) => $query->where('merchant_id', $merchantId),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            );

        if ($user instanceof User) {
            $query->whereHas('users', fn (Builder $query) => $query->where('users.id', $user->id));
        }

        return $query;
    }

    public static function accessibleBranchesQuery(Merchant|User|null $user, ?string $businessId = null): Builder
    {
        $merchantId = static::merchantIdFor($user);

        $query = Branch::query()
            ->withoutTrashed()
            ->when(
                filled($merchantId),
                fn (Builder $query) => $query->where('merchant_id', $merchantId),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->when(
                filled($businessId),
                fn (Builder $query) => $query->where('business_id', $businessId)
            );

        if ($user instanceof User) {
            $query
                ->whereHas('users', fn (Builder $query) => $query->where('users.id', $user->id))
                ->whereHas('business.users', fn (Builder $query) => $query->where('users.id', $user->id));
        }

        return $query;
    }

    public static function partyAliasFor(?string $partyType): ?string
    {
        return match ($partyType) {
            Customer::class => 'customer',
            Vendor::class => 'vendor',
            default => null,
        };
    }

    public static function partyClassForAlias(?string $partyType): ?string
    {
        return match ($partyType) {
            'customer' => Customer::class,
            'vendor' => Vendor::class,
            default => null,
        };
    }

    public static function visiblePartyRecord(string $partyAlias, string $partyId): Customer|Vendor|null
    {
        $partyClass = static::partyClassForAlias($partyAlias);
        $user = Filament::auth()->user();

        if ($partyClass === Customer::class) {
            return CustomerResource::scopeVisibleCustomers(
                Customer::query()->whereKey($partyId),
                $user,
            )->first();
        }

        if ($partyClass === Vendor::class) {
            return VendorResource::scopeVisibleVendors(
                Vendor::query()->whereKey($partyId),
                $user,
            )->first();
        }

        return null;
    }

    public static function partyCashFlowsQuery(string $partyAlias, string $partyId): Builder
    {
        $user = Filament::auth()->user();
        $partyClass = static::partyClassForAlias($partyAlias);
        $merchantId = static::merchantIdFor($user);

        $query = CashFlow::query()
            ->withoutTrashed()
            ->whereNull('settlement_for_id')
            ->where('merchant_id', $merchantId)
            ->where('party_type', $partyClass)
            ->where('party_id', $partyId);

        if ($user instanceof User) {
            $businessIds = $user->businesses()->pluck('businesses.id');
            $branchIds = $user->branches()->pluck('branches.id');

            if ($businessIds->isEmpty() || $branchIds->isEmpty()) {
                return $query->whereRaw('1 = 0');
            }

            $query
                ->whereIn('business_id', $businessIds)
                ->whereIn('branch_id', $branchIds);
        }

        return $query;
    }
}
