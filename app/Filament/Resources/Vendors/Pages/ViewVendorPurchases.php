<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Purchase;
use App\Models\Vendor;
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

class ViewVendorPurchases extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = VendorResource::class;

    protected string $view = 'filament.resources.vendors.pages.view-vendor-purchases';

    public Vendor $record;

    public function mount(Vendor $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return "Vendor Purchases - {$this->record->name}";
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
                    return Purchase::query()->whereRaw('1 = 0');
                }

                $query = Purchase::query()
                    ->withoutTrashed()
                    ->where('merchant_id', $merchantId)
                    ->where('vendor_id', $this->record->id)
                    ->whereHas('items', fn ($q) => $q->where('quantity', '>', 0));

                if ($user instanceof \App\Models\User) {
                    $query->whereHas('items.branch.users', fn ($q) => $q->where('users.id', $user->id));
                }

                return $query;
            })
            ->columns([
                TextColumn::make('purchase_no')
                    ->label('Purchase No.')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Purchase $record) => PurchaseResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('purchase_date')
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
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Purchase $record) => PurchaseResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('purchases.update', Filament::getCurrentPanel()->getAuthGuard())),

//                EditAction::make('quick_edit')
//                    ->label('Quick Edit')
//                    ->icon('heroicon-o-pencil')
//                    ->slideOver()
//                    ->visible(fn (Purchase $record) => auth(Filament::getCurrentPanel()->getAuthGuard())
//                        ->user()?->hasPermissionTo('purchases.update', Filament::getCurrentPanel()->getAuthGuard())
//                        && ! $record->returns()->exists())
//                    ->form([
//                        DatePicker::make('purchase_date')
//                            ->required(),
//                        Textarea::make('notes')
//                            ->rows(4)
//                            ->columnSpanFull(),
//                    ]),
            ])
            ->defaultSort('purchase_date', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('edit_vendor')
                ->label('Edit Vendor')
                ->icon('heroicon-o-pencil-square')
                ->url(VendorResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
