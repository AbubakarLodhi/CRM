<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Filament\Resources\Roles\RolesResource;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RolesForm
{
    public static function configure(Schema $schema): Schema
    {
        $assignable = RolesResource::getAssignablePermissionMatrix();

        return $schema
            ->columns(1)
            ->components([
                Section::make('Role Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Role Name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                $livewire->resetValidation('data.name');
                                $livewire->resetErrorBag('data.name');
                            }),



        ])
                    ->columns(1),

                Section::make('Permissions')
                    ->schema([
                        Checkbox::make('permissions_select_all')
                            ->label('Select All')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) use ($assignable) {
                                foreach ($assignable as $module => $actions) {
                                    $set("permissions.{$module}.enabled", $state);
                                    foreach ($actions as $action) {
                                        $set("permissions.{$module}.{$action}", $state);
                                    }
                                }
                            })
                            ->dehydrated(false),

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
        $assignable = RolesResource::getAssignablePermissionMatrix();

        // 🔹 DISPLAY-ONLY ALIASES (NO FUNCTIONAL IMPACT)
        $moduleAliases = [
            'users' => 'Staff',
        ];

        $rows = [];

        foreach ($assignable as $module => $actions) {
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
