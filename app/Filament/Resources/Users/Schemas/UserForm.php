<?php

namespace App\Filament\Resources\Users\Schemas;


use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->unique(User::class, 'email')
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label('Email Verified At')
                    ->displayFormat('m/d/Y H:i:s')
                    ->seconds()
                    ->disabled(fn (callable $get) => $get('status') !== User::STATUS_VERIFIED)
                    ->helperText('Auto-filled when status is Verified'),



                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->hiddenOn('edit'),
                FileUpload::make('profile_photo')
                    ->label('Profile Photo')
                    ->image()
                    ->disk('public')
                    ->directory('staff/profile-photos')
                    ->imagePreviewHeight(150)
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->saveUploadedFileUsing(fn ($file) =>
                    $file->store('staff/profile-photos', 'public')
                    ),
                Select::make('status')
                    ->options([
                        User::STATUS_PENDING  => 'Pending',
                        User::STATUS_VERIFIED => 'Verified',
                        User::STATUS_REJECTED => 'Rejected',
                    ])
                    ->required()
                    ->default(User::STATUS_PENDING)
                    ->live()
                    ->afterStateUpdated(function (callable $set, $state) {

                        if ($state === User::STATUS_VERIFIED) {
                            // ✅ Auto-set email verification time
                            $set('email_verified_at', now());

                            // ✅ Allow activation
                            $set('is_active', true);
                        } else {
                            // ❌ Clear verification
                            $set('email_verified_at', null);

                            // ❌ Force inactive
                            $set('is_active', false);
                        }
                    }),


                Hidden::make('merchant_id')
                    ->default(fn() => Filament::auth()->user()?->id),

                Section::make('Access Control')
                    ->schema([
                        Select::make('businesses')
                            ->label('Businesses')
                            ->multiple()
                            ->relationship(
                                'businesses',
                                'name',
                                fn ($query) => $query->where(
                                    'merchant_id',
                                    Filament::auth()->id()
                                )
                            )
                            ->preload()
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get, $state, $old) {
                                if ($old !== null) {
                                    $set('branches', []);
                                }
                            }),

                        Select::make('branches')
                            ->label('Branches')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()

                            // ✅ Tell Filament how to resolve label for a selected value
                            ->getOptionLabelUsing(function ($value): ?string {
                                return \App\Models\Branch::find($value)?->name;
                            })

                            // ✅ Rehydrate selected branches on edit
                            ->afterStateHydrated(function (callable $set, ?User $record) {
                                if ($record) {
                                    $set(
                                        'branches',
                                        $record->branches()->pluck('branches.id')->toArray()
                                    );
                                }
                            })

                            // ✅ Options filtered by selected businesses
                            ->options(function (callable $get) {
                                $businessIds = $get('businesses') ?? [];

                                if (blank($businessIds)) {
                                    return [];
                                }

                                return \App\Models\Branch::query()
                                    ->whereIn('business_id', $businessIds)
                                    ->when(
                                        fn ($q) => $q->where('merchant_id', Filament::auth()->id())
                                    )
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }),

                    ])
                    ->columnSpanFull()
                    ->columns(2),

                Grid::make(2)
                    ->schema([
                        Select::make('roles')
                            ->label('Roles')
                            ->multiple()
                            ->relationship(
                                'roles',
                                'name',
                                fn ($query) => $query->where('guard_name', 'staff')
                            )
                            ->preload()
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Is active')
                            ->default(false)
                            ->disabled(fn (callable $get) => $get('status') !== User::STATUS_VERIFIED)
                            ->dehydrated()
                            ->inline(false),

                    ])
                    ->columnSpanFull()
                    ->columns(2),






            ]);
    }
}
