<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Merchant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function persistsFiltersInSession(): bool
    {
        return false;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Filters')
                    ->description('Refine dashboard analytics by business, branch, and date range.')
                    ->extraAttributes(['class' => 'dashboard-filters-section'])
                    ->schema([
                        Select::make('business_id')
                            ->label('Business')
                            ->placeholder('All businesses')
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                $user = Filament::auth()->user();

                                $merchantId = match (true) {
                                    $user instanceof Merchant => $user->id,
                                    $user instanceof User     => $user->merchant_id,
                                    default                   => null,
                                };

                                if (! $merchantId) {
                                    return [];
                                }

                                $query = Business::query()
                                    ->where('merchant_id', $merchantId);

                                if ($user instanceof User) {
                                    $query->whereHas('users', fn ($q) =>
                                        $q->where('users.id', $user->id)
                                    );
                                }

                                return $query
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('branch_id', null)),

                        Select::make('branch_id')
                            ->label('Branch')
                            ->placeholder('All branches')
                            ->searchable()
                            ->preload()
                            ->options(function (callable $get) {
                                $user = Filament::auth()->user();

                                $merchantId = match (true) {
                                    $user instanceof Merchant => $user->id,
                                    $user instanceof User     => $user->merchant_id,
                                    default                   => null,
                                };

                                if (! $merchantId) {
                                    return [];
                                }

                                $query = Branch::query()
                                    ->where('merchant_id', $merchantId);

                                if ($businessId = $get('business_id')) {
                                    $query->where('business_id', $businessId);
                                }

                                if ($user instanceof User) {
                                    $query->whereHas('users', fn ($q) =>
                                        $q->where('users.id', $user->id)
                                    );
                                }

                                return $query
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }),

                        DatePicker::make('date_from')
                            ->label('Date From')
                            ->placeholder('Start date')
                            ->displayFormat('d/m/Y')
                            ->maxDate(now())
                            ->rule('before_or_equal:today')
                            ->suffixAction(
                                \Filament\Actions\Action::make('clear_date_from')
                                    ->icon('heroicon-o-x-mark')
                                    ->tooltip('Clear date')
                                    ->action(fn (callable $set) => $set('date_from', null))
                            )
                            ->native(false),

                        DatePicker::make('date_to')
                            ->label('Date To')
                            ->placeholder('End date')
                            ->displayFormat('d/m/Y')
                            ->minDate(fn (callable $get) => $get('date_from'))
                            ->maxDate(now())
                            ->rule('before_or_equal:today')
                            ->rule('after_or_equal:date_from')
                            ->suffixAction(
                                \Filament\Actions\Action::make('clear_date_to')
                                    ->icon('heroicon-o-x-mark')
                                    ->tooltip('Clear date')
                                    ->action(fn (callable $set) => $set('date_to', null))
                            )
                            ->native(false),
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'xl' => 4,
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
