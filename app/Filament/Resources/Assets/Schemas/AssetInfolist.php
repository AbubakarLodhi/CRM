<?php

namespace App\Filament\Resources\Assets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Asset Identity')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('asset_code')->label('Asset Code'),
                    TextEntry::make('name')->label('Asset Name'),
                    TextEntry::make('assetType.name')->label('Asset Type'),
                    TextEntry::make('serial_number')->label('Serial Number')->placeholder('—'),
                    TextEntry::make('model_number')->label('Model Number')->placeholder('—'),
                    TextEntry::make('manufacturer')->label('Manufacturer')->placeholder('—'),
                ]),

            Section::make('Location & Assignment')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('business.name')->label('Business'),
                    TextEntry::make('branch.name')->label('Branch'),
                    TextEntry::make('location')->label('Location')->placeholder('—'),
                    TextEntry::make('assignedUser.name')->label('Assigned To')->placeholder('—'),
                    TextEntry::make('vendor.name')->label('Supplier / Vendor')->placeholder('—'),
                    TextEntry::make('createdBy.name')->label('Created By')->placeholder('—'),
                ]),

            Section::make('Financial & Lifecycle')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('purchase_date')->label('Purchase Date')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('purchase_cost')->label('Purchase Cost')->money('PKR')->placeholder('—'),
                    TextEntry::make('current_value')->label('Current Value')->money('PKR')->placeholder('—'),
                    TextEntry::make('warranty_expiry')->label('Warranty Expiry')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('condition')->label('Condition')->badge(),
                ]),

            Section::make('Details')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('description')->label('Description')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('notes')->label('Notes')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }
}
