<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Purchase;
use App\Models\Vendor;
use Filament\Actions\Action as HeaderAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action as TableAction;
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
                    ->where('vendor_id', $this->record->id);

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
                    ->url(fn (Purchase $record) => PurchaseResource::getUrl('view', ['record' => $record])),
                TextColumn::make('purchase_date')
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
                    ->getStateUsing(function (Purchase $record): string {
                        if (! $record->returns()->exists()) {
                            return '-';
                        }

                        $hasRemaining = $record->items()->where('quantity', '>', 0)->exists();

                        return $hasRemaining ? 'Partially Returned' : 'Returned';
                    }),
            ])
            ->recordActions([
                TableAction::make('summary')
                    ->label('Summary')
                    ->tooltip('Payment Summary')
                    ->icon('heroicon-s-document-text')
                    ->color('info')
                    ->modalHeading(fn (Purchase $record) => 'Purchase Summary - ' . (string) ($record->purchase_no ?? ''))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (Purchase $record) => view('filament.partials.payment-summary-modal', [
                        'document' => $record,
                        'documentType' => 'purchase',
                    ]))
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('purchases.view', Filament::getCurrentPanel()->getAuthGuard())),

                TableAction::make('open_record')
                    ->label('Open')
                    ->tooltip('Open')
                    ->icon('heroicon-s-pencil-square')
                    ->url(fn (Purchase $record) => PurchaseResource::getUrl('view', ['record' => $record]))
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('purchases.view', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->defaultSort('purchase_date', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('edit_vendor')
                ->label('Edit Vendor')
                ->icon('heroicon-s-pencil-square')
                ->url(VendorResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
