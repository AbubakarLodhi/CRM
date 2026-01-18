<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\PermissionModule;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class RolesForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentGuard = Filament::getCurrentPanel()->getAuthGuard();

        $guardLabels = [
            'merchant' => 'merchant',
            'staff' => 'staff',
        ];
        return $schema
            ->columns(1)
            ->components([
                Section::make('Role Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Role Name')
                            ->required()
                            ->unique()
                            ->maxLength(255)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                $livewire->resetValidation('data.name');
                                $livewire->resetErrorBag('data.name');
                            }),
//                         //   ->unique(ignoreRecord: true),



        ])
                    ->columns(1),

                Section::make('Permissions')
                    ->schema([
                        Fieldset::make('Assign Permissions')
                            ->schema(static::getPermissionRows())
                            ->columns(1)
                            ->statePath('permissions')
                            ->reactive(),
                    ]),
            ]);
    }

    protected static function getPermissionRows(): array
    {
        $enabledModules = PermissionModule::enabledForCurrentMerchant();
        $actions = ['view', 'create', 'update', 'delete'];

        // 🔹 DISPLAY-ONLY ALIASES (NO FUNCTIONAL IMPACT)
        $moduleAliases = [
            'users' => 'Staff',
        ];

        $rows = [];

        foreach ($enabledModules as $module) {
            // Resolve label with alias fallback
            $label = $moduleAliases[$module]
                ?? ucfirst(str_replace('_', ' ', $module));

            $rows[] = Grid::make()
                ->schema([
                    // ✅ Module checkbox (enabler)
                    Checkbox::make("{$module}.enabled")
                        ->label($label)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) use ($module, $actions) {
                            if (! $state) {
                                foreach ($actions as $action) {
                                    $set("{$module}.{$action}", false);
                                }
                            }
                        })
                        ->dehydrated(false),

                    // ✅ Action checkboxes
                    ...collect($actions)->map(fn ($action) =>
                    Checkbox::make("{$module}.{$action}")
                        ->label(ucfirst($action))
                        ->default(false)
                        ->disabled(fn (callable $get) =>
                        ! $get("{$module}.enabled")
                        )
                    )->toArray(),
                ])
                ->columns(5);
        }

        return $rows;
    }



}
