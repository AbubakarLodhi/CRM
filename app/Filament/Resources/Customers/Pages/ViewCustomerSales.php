<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Models\Customer;
use App\Models\Sale;
use Filament\Actions\Action as HeaderAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViewCustomerSales extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CustomerResource::class;

    protected string $view = 'filament.resources.customers.pages.view-customer-sales';

    public Customer $record;

    public function mount(Customer $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return "Customer Sales - {$this->record->name}";
    }

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->query(function () use ($user): Builder {
                $merchantId = match (true) {
                    $user instanceof \App\Models\Merchant => $user->id,
                    $user instanceof \App\Models\User => $user->merchant_id,
                    default => null,
                };

                if (! $merchantId) {
                    return Sale::query()->whereRaw('1 = 0');
                }

                $query = Sale::query()
                    ->withoutTrashed()
                    ->where('merchant_id', $merchantId)
                    ->where('customer_id', $this->record->id);

                if ($user instanceof \App\Models\User) {
                    $query
                        ->whereHas('items.business.users', fn ($q) => $q->where('users.id', $user->id))
                        ->whereHas('items.branch.users', fn ($q) => $q->where('users.id', $user->id));
                }

                return $query;
            })
            ->columns([
                TextColumn::make('sale_no')
                    ->label('Sale No.')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Sale $record) => $record->returns()->exists()
                        ? SaleResource::getUrl('view', ['record' => $record])
                        : SaleResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('sale_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total (PKR)')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('Paid (PKR)')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('due_amount')
                    ->label('Due (PKR)')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('payment_type')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ucfirst((string) $state)),
                TextColumn::make('return_status')
                    ->label('Return')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Returned' => 'success',
                        'Partially Returned' => 'warning',
                        default => 'gray',
                    })
                    ->getStateUsing(function (Sale $record): string {
                        if (! $record->returns()->exists()) {
                            return '-';
                        }

                        $hasRemaining = $record->items()->where('quantity', '>', 0)->exists();

                        return $hasRemaining ? 'Partially Returned' : 'Returned';
                    }),
            ])
            ->recordActions([
                TableAction::make('open_record')
                    ->label('Open')
                    ->tooltip('Open')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Sale $record) => $record->returns()->exists()
                        ? SaleResource::getUrl('view', ['record' => $record])
                        : SaleResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('sales.view', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->defaultSort('sale_date', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('edit_customer')
                ->label('Edit Customer')
                ->icon('heroicon-o-pencil-square')
                ->url(CustomerResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
