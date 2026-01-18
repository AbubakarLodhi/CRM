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
                Section::make('Personal Information')
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->unique(User::class, 'email')
                    ->required()
                    ->live()
                    ->maxLength(255)
                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                        $livewire->resetValidation('data.email');
                        $livewire->resetErrorBag('data.email');
                    }),
//                DateTimePicker::make('email_verified_at')
//                    ->label('Email Verified At')
//                    ->displayFormat('m/d/Y H:i:s')
//                    ->seconds()
//                    ->minDate(fn () => now()->subMinutes(1))
//                    ->disabled(fn (callable $get) => $get('status') !== User::STATUS_VERIFIED)
//                    ->helperText('Auto-filled when status is Verified'),



                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->maxLength(255)
                    ->hiddenOn('edit'),
//                FileUpload::make('profile_photo')
//                    ->label('Profile Photo')
//                    ->image()
//                    ->disk('public')
//                    ->directory('staff/profile-photos')
//                    ->visibility('public')   // ✅ ADD THIS
//                    ->imagePreviewHeight(150)
//                    ->maxSize(2048)
//                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
//                    ->dehydrated(false),

//                Select::make('status')
//                    ->options([
//                        User::STATUS_PENDING  => 'Pending',
//                        User::STATUS_VERIFIED => 'Verified',
//                        User::STATUS_REJECTED => 'Rejected',
//                    ])
//                    ->required()
//                    ->default(User::STATUS_PENDING)
//                    ->live()
//                    ->afterStateUpdated(function (callable $set, $state) {
//
//                        if ($state === User::STATUS_VERIFIED) {
//                            // ✅ Auto-set email verification time
//                            $set('email_verified_at', now());
//
//                            // ✅ Allow activation
//                            $set('is_active', true);
//                        } else {
//                            // ❌ Clear verification
//                            $set('email_verified_at', null);
//
//                            // ❌ Force inactive
//                            $set('is_active', false);
//                        }
//                    }),
                Select::make('status')
                    ->options([
                        User::STATUS_PENDING  => 'Pending',
                        User::STATUS_VERIFIED => 'Verified',
                        User::STATUS_REJECTED => 'Rejected',
                    ])
                    ->required()
                    //->default(User::STATUS_PENDING)
                    ->live()
                    ->afterStateUpdated(function (callable $set, $state) {

                        if ($state === User::STATUS_VERIFIED) {
                            // ✅ Auto-verify
                            $set('email_verified_at', now());
                            $set('is_active', true);
                        } else {
                            // ❌ Unverify
                            $set('email_verified_at', null);
                            $set('is_active', false);
                        }
                    }),


                Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->relationship(
                        'roles',
                        'name',
                        fn ($query) => $query->where('guard_name', 'staff')
                    )
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                        $livewire->resetValidation('data.roles');
                        $livewire->resetErrorBag('data.roles');
                    }),
                Grid::make(2)
                    ->schema([

                        Toggle::make('is_active')
                            ->label('Is active')
                            ->default(false)
                            ->disabled(fn (callable $get) => $get('status') !== User::STATUS_VERIFIED)
                            ->dehydrated()
                            ->inline(false),

                    ])
                    ->columnSpanFull()
                    ->columns(2),
            ]) ->columnSpanFull()
                    ->columns(2),

                Hidden::make('merchant_id')
                    ->default(function () {
                        $user = Filament::auth()->user();

                        return $user?->merchant_id ?? $user?->id;
                    }),


                Section::make('Access Control')
                    ->schema([
                        Select::make('businesses')
                            ->label('Businesses')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()

                            // 🔹 OPTIONS (scoped)
                            ->options(function () {
                                $user = Filament::auth()->user();

                                if ($user->id === $user->merchant_id) {
                                    return \App\Models\Business::where('merchant_id', $user->merchant_id)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                }

                                return $user->businesses()
                                    ->pluck('businesses.name', 'businesses.id')
                                    ->toArray();
                            })

                            // 🔹 IMPORTANT: rehydrate selected businesses on edit
                            ->afterStateHydrated(function (callable $set, ?User $record) {
                                if ($record) {
                                    $set(
                                        'businesses',
                                        $record->businesses()->pluck('businesses.id')->toArray()
                                    );
                                }
                            })

                            // 🔹 ALWAYS resolve label (even if option missing)
                            ->getOptionLabelUsing(
                                fn ($value) => \App\Models\Business::find($value)?->name
                            )

                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('branches', [])),

                        Select::make('branches')
                            ->label('Branches')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()

                            ->options(function (callable $get) {
                                $businessIds = $get('businesses') ?? [];

                                if (blank($businessIds)) {
                                    return [];
                                }

                                $user = Filament::auth()->user();

                                if ($user->id === $user->merchant_id) {
                                    return \App\Models\Branch::whereIn('business_id', $businessIds)
                                        ->where('merchant_id', $user->merchant_id)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                }

                                return $user->branches()
                                    ->whereIn('business_id', $businessIds)
                                    ->pluck('branches.name', 'branches.id')
                                    ->toArray();
                            })

                            // 🔹 CRITICAL: resolve label for existing value
                            ->getOptionLabelUsing(
                                fn ($value) => \App\Models\Branch::find($value)?->name
                            )

                            // 🔹 CRITICAL: rehydrate existing branches
                            ->afterStateHydrated(function (callable $set, ?User $record) {
                                if ($record) {
                                    $set(
                                        'branches',
                                        $record->branches()->pluck('branches.id')->toArray()
                                    );
                                }
                            }),



                    ])
                    ->columnSpanFull()
                    ->columns(2),








            ]);
    }
}
