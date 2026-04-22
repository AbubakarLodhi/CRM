<?php

namespace App\Filament\Resources\CashFlows\Pages;

use App\Filament\Resources\CashFlows\CashFlowResource;
use App\Filament\Resources\CashFlows\Tables\CashFlowsTable;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\Vendor;
use Filament\Actions\Action as HeaderAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewPartyCashFlows extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CashFlowResource::class;

    protected string $view = 'filament.resources.cash-flows.pages.view-party-cash-flows';

    public Customer|Vendor $party;

    public string $partyType;

    public function mount(string $partyType, string $partyId): void
    {
        $party = CashFlowResource::visiblePartyRecord($partyType, $partyId);

        abort_unless($party, 404);

        $this->partyType = $partyType;
        $this->party = $party;
    }

    public function getTitle(): string
    {
        $label = match (true) {
            $this->party instanceof Customer => 'Customer',
            $this->party instanceof Vendor => 'Vendor',
            default => 'Party',
        };

        return "{$label} Cash Flows - {$this->party->name}";
    }

    public function table(Table $table): Table
    {
        return CashFlowsTable::configureDetail(
            $table->query(
                CashFlowResource::partyCashFlowsQuery(
                    $this->partyType,
                    (string) $this->party->getKey(),
                )
            )
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('back')
                ->label('Back')
                ->icon('heroicon-s-arrow-left')
                ->url(CashFlowResource::getUrl('index')),
        ];
    }
}
