<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Vendor;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Asset Identity')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('asset_code')
                        ->label('Asset Code')
                        ->required()
                        ->maxLength(100)
                        ->default(fn () => 'AST-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -6)))
                        ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                            $user = Filament::auth()->user();
                            $merchantId = match (true) {
                                $user instanceof Merchant => $user->id,
                                $user instanceof User => $user->merchant_id,
                                default => null,
                            };

                            if ($merchantId) {
                                $rule->where('merchant_id', $merchantId);
                            }

                            return $rule;
                        }),

                    TextInput::make('name')
                        ->label('Asset Name')
                        ->required()
                        ->maxLength(255),

                    Select::make('asset_type_id')
                        ->label('Asset Type')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => self::assetTypeOptions()),

                    TextInput::make('serial_number')
                        ->label('Serial Number')
                        ->maxLength(255),

                    TextInput::make('model_number')
                        ->label('Model Number')
                        ->maxLength(255),

                    TextInput::make('manufacturer')
                        ->label('Manufacturer')
                        ->maxLength(255),

                    Hidden::make('merchant_id')
                        ->default(fn () => self::resolveMerchantId())
                        ->required(),

                    Hidden::make('created_by')
                        ->default(fn () => Filament::auth()->user() instanceof User
                            ? Filament::auth()->id()
                            : null),
                ]),

            Section::make('Location & Assignment')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Select::make('branch_id')
                        ->label('Branch')
                        ->required()
                        ->searchable()
                        ->allowHtml()
                        ->options(fn (): array => self::branchOptions()),

                    TextInput::make('location')
                        ->label('Location / Room')
                        ->maxLength(255)
                        ->placeholder('e.g. Warehouse A, Office 2'),

                    Select::make('assigned_to')
                        ->label('Assigned To')
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => self::staffOptions()),

                    Select::make('vendor_id')
                        ->label('Supplier / Vendor')
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => self::vendorOptions()),
                ]),

            Section::make('Financial & Lifecycle')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    DatePicker::make('purchase_date')
                        ->label('Purchase Date')
                        ->displayFormat('d/m/Y'),

                    TextInput::make('purchase_cost')
                        ->label('Purchase Cost (PKR)')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),

                    TextInput::make('current_value')
                        ->label('Current Value (PKR)')
                        ->numeric()
                        ->minValue(0),

                    DatePicker::make('warranty_expiry')
                        ->label('Warranty Expiry')
                        ->displayFormat('d/m/Y'),

                    Select::make('status')
                        ->label('Status')
                        ->options(AssetStatus::options())
                        ->default(AssetStatus::Active->value)
                        ->required(),

                    Select::make('condition')
                        ->label('Condition')
                        ->options(AssetCondition::options())
                        ->default(AssetCondition::Good->value)
                        ->required(),
                ]),

            Section::make('Details')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),

                    FileUpload::make('attachment')
                        ->label('Attach File')
                        ->disk('public')
                        ->directory('assets/files')
                        ->visibility('public')
                        ->maxSize(1024)
                        ->maxFiles(1)
                        ->acceptedFileTypes([
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->helperText('Maximum file size: 1 MB.')
                        ->validationMessages([
                            'max' => 'The file size should be at most 1 MB.',
                        ])
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private static function assetTypeOptions(): array
    {
        $merchantId = self::resolveMerchantId();

        if (! $merchantId) {
            return [];
        }

        return AssetType::query()
            ->where('merchant_id', $merchantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function branchOptions(): array
    {
        $user = Filament::auth()->user();

        $query = Branch::query()
            ->withoutTrashed()
            ->with('business')
            ->where('is_active', true);

        if ($user instanceof Merchant) {
            $query->where('merchant_id', $user->id);
        }

        if ($user instanceof User) {
            $query->whereIn('branches.id', $user->branches()->pluck('branches.id'));
        }

        return $query
            ->orderBy('business_id')
            ->orderBy('branches.name')
            ->get()
            ->groupBy(fn ($branch) => $branch->business?->name ?? 'Other')
            ->map(fn ($group) => $group->pluck('name', 'id')
                ->map(fn ($name) => '&nbsp;&nbsp;&nbsp;&nbsp;'.e($name))
                ->all())
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function staffOptions(): array
    {
        $merchantId = self::resolveMerchantId();

        if (! $merchantId) {
            return [];
        }

        return User::query()
            ->where('merchant_id', $merchantId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function vendorOptions(): array
    {
        $merchantId = self::resolveMerchantId();

        if (! $merchantId) {
            return [];
        }

        return Vendor::query()
            ->withoutTrashed()
            ->where('merchant_id', $merchantId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function resolveMerchantId(): ?string
    {
        $user = Filament::auth()->user();

        return match (true) {
            $user instanceof Merchant => $user->id,
            $user instanceof User => $user->merchant_id,
            default => null,
        };
    }
}
