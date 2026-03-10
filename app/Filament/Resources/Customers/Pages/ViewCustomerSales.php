<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Models\Customer;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Actions\Action as HeaderAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\Page;
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
                    ->where('customer_id', $this->record->id)
                    ->whereHas('items', fn ($q) => $q->where('quantity', '>', 0));

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
                    ->url(fn (Sale $record) => SaleResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('sale_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('due_amount')
                    ->label('Due')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('payment_type')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ucfirst((string) $state)),
            ])
            ->recordActions([
                Action::make('open_edit')
                    ->label('')
                    ->tooltip('Edit')
                    ->label('Open Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Sale $record) => SaleResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('sales.update', Filament::getCurrentPanel()->getAuthGuard())),

//                EditAction::make('quick_edit')
//                    ->label('Quick Edit')
//                    ->icon('heroicon-o-pencil')
//                    ->slideOver()
//                    ->visible(fn (Sale $record) => auth(Filament::getCurrentPanel()->getAuthGuard())
//                        ->user()?->hasPermissionTo('sales.update', Filament::getCurrentPanel()->getAuthGuard())
//                        && ! $record->returns()->exists())
//                    ->form([
//                        DatePicker::make('sale_date')
//                            ->required(),
//                        Textarea::make('notes')
//                            ->rows(4)
//                            ->columnSpanFull(),
//                    ]),
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
