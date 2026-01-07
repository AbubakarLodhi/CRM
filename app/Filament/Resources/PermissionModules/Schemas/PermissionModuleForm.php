<?php

namespace App\Filament\Resources\PermissionModules\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PermissionModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('module')
                ->label('Module Key')
                ->required()
                ->unique(ignoreRecord: true)
                ->regex('/^[a-z_]+$/')
                ->maxLength(100)
                ->helperText(
                    'Use lowercase snake_case only. Example: orders, sales_reports'
                ),

            TextInput::make('label')
                ->label('Module Name')
                ->required()
                ->maxLength(150)
                ->helperText(
                    'Human readable name. Example: Orders, Sales Reports'
                ),
        ]);
    }
}
