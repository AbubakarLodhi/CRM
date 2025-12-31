<?php

namespace App\Filament\Resources\Merchants\Tables;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Models\Branch;
use App\Models\Merchant;
use App\Models\MerchantSetting;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;

class MerchantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile_photo')
                    ->label('Photo')
                    ->size(50)
                    ->square()
                    ->getStateUsing(fn (Merchant $record) =>
                    $record->profilePhoto
                        ? asset('storage/' . $record->profilePhoto->photo_url)
                        : asset('storage/placeholder/placeholder.jpg')
                    ),



                TextColumn::make('name')
                    ->searchable(),

                ImageColumn::make('merchant_logo')
                    ->label('Logo')
                    ->size(50)
                    ->square()
                    ->getStateUsing(fn (Merchant $record) =>
                    $record->logo
                        ? asset('storage/' . $record->logo->photo_url)
                        : asset('storage/placeholder/placeholder.jpg')
                    ),
//                ImageColumn::make('merchant_logo')
//                    ->label('Logo')
//                    ->size(40)
//                    ->square()
//                    ->getStateUsing(fn (Merchant $record) =>
//                    $record->logo
//                        ? asset('storage/' . $record->logo->photo_url)
//                        : null
//                    )
//                    ->defaultImageUrl(asset('images/brand-placeholder.png')),

                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('address_line_1')
                    ->searchable(),
                TextColumn::make('address_line_2')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('website')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'pending',
                        'success' => 'verified',
                        'danger' => 'rejected',
                    ])
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
                SelectFilter::make('is_active')
                    ->label('Active')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        Merchant::STATUS_PENDING => 'Pending',
                        Merchant::STATUS_VERIFIED => 'Verified',
                        Merchant::STATUS_REJECTED => 'Rejected',
                    ])
                    ->label('Status'),
                SelectFilter::make('city')
                    ->label('City')
                    ->options(
                        Merchant::distinct()
                            ->pluck('city', 'city')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
            ])
            ->recordActions([
                Action::make('settings')
                    ->label('')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->tooltip('Merchant Settings')
                    ->modalHeading('Merchant Settings')
                    ->modalSubmitActionLabel('Save Settings')
                    ->modalWidth('xl')

                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()
                        ?->hasPermissionTo('merchants.update', Filament::getCurrentPanel()->getAuthGuard())
                    )

                    // 👉 Prefill form if settings exist
                    ->mountUsing(function (Action $action, Merchant $record) {

                        $settings = $record->settings;

                        if (! $settings) {
                            return;
                        }

                        $action->fillForm([
                            // 🔹 Normal fields
                            'primary_color'   => $settings->primary_color,
                            'secondary_color' => $settings->secondary_color,
                            'currency'        => $settings->currency,
                            'timezone'        => $settings->timezone,

                            // 🔹 FileUpload fields MUST be arrays
                            'merchant_logo' => $record->logo
                                ? [$record->logo->photo_url]
                                : null,

                            'profile_photo' => $record->profilePhoto
                                ? [$record->profilePhoto->photo_url]
                                : null,
                        ]);
                    })


                    ->form([
                        FileUpload::make('merchant_logo')
                            ->label('Merchant Logo')
                            ->image()
                            ->disk('public')
                            ->directory('merchants/logos')
                            ->imagePreviewHeight(120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->dehydrated(false),

                        FileUpload::make('profile_photo')
                            ->label('Profile Photo')
                            ->image()
                            ->disk('public')
                            ->directory('merchants/profile-photos')
                            ->imagePreviewHeight(120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->dehydrated(false),

                        ColorPicker::make('primary_color')->required(),
                        ColorPicker::make('secondary_color')->required(),

                        TextInput::make('currency')
                            ->required()
                            ->default('USD'),

                        TextInput::make('timezone')
                            ->required()
                            ->default('UTC'),
                    ])

                    // 👉 UPSERT logic
                    ->action(function (array $data, Merchant $record) {

                        // 1️⃣ Create or update settings
                        MerchantSetting::updateOrCreate(
                            ['merchant_id' => $record->id],
                            Arr::only($data, [
                                'primary_color',
                                'secondary_color',
                                'currency',
                                'timezone',
                            ])
                        );

                        // 2️⃣ PROFILE PHOTO
                        if (!empty($data['profile_photo'])) {
                            $record->profilePhoto()?->delete();

                            $record->profilePhoto()->create([
                                'merchant_id' => $record->id,
                                'type'        => AttachmentType::IMAGE,
                                'meta_type'   => AttachmentMetaType::PROFILE_PHOTO,
                                'photo_url'   => collect($data['profile_photo'])->first(),
                            ]);
                        }

                        // 3️⃣ MERCHANT LOGO
                        if (!empty($data['merchant_logo'])) {
                            $record->logo()?->delete();

                            $record->logo()->create([
                                'merchant_id' => $record->id,
                                'type'        => AttachmentType::IMAGE,
                                'meta_type'   => AttachmentMetaType::MERCHANT_LOGO,
                                'photo_url'   => collect($data['merchant_logo'])->first(),
                            ]);
                        }
                    }),

                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('merchants.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('merchants.delete', Filament::getCurrentPanel()->getAuthGuard()))
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('merchants.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ]);
    }
}
