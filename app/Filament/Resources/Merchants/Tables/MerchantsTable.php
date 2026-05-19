<?php

namespace App\Filament\Resources\Merchants\Tables;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Merchants\MerchantResource;
use App\Models\Branch;
use App\Models\Merchant;
use App\Models\MerchantPermissionModule;
use App\Models\MerchantSetting;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use App\Models\PermissionModule;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;



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
                        : asset('images/placeholder.jpg')
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
                        : asset('images/placeholder.jpg')
                    ),
//

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
                    ->boolean()
                    ->color(fn ($state) => $state ? 'primary' : 'danger'),
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
            ->recordUrl(fn (Merchant $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('merchants.update', Filament::getCurrentPanel()->getAuthGuard())
                ? MerchantResource::getUrl('edit', [
                'record' => $record,
            ])
                : null
            )

            ->recordActions([
                Action::make('modules')
                    ->label('')
                    ->icon('heroicon-s-key')
                    ->tooltip('Manage Permission Modules')
                    ->modalHeading('Enable Modules for Merchant')
                    ->modalSubmitActionLabel('Save')
                    ->modalWidth('lg')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()
                        ?->hasPermissionTo('merchants.update', Filament::getCurrentPanel()->getAuthGuard())
                    )

                    // ================= FORM =================
                    ->form([
                        CheckboxList::make('modules')
                            ->label('Enabled Modules')
                            ->options(
                                PermissionModule::orderBy('label')
                                    ->pluck('label', 'id')
                                    ->toArray()
                            )
                            ->columns(2)
                            ->searchable(),
                    ])

                    // ================= FILL =================
                    ->fillForm(function (Merchant $record): array {
                        return [
                            'modules' => MerchantPermissionModule::where('merchant_id', $record->id)
                                ->pluck('permission_module_id')
                                ->toArray(),
                        ];
                    })

                    // ================= SAVE =================
                    ->action(function (array $data, Merchant $record) {

                        DB::transaction(function () use ($data, $record) {

                            // 🔥 DELETE existing
                            MerchantPermissionModule::where('merchant_id', $record->id)->delete();

                            // 🔥 INSERT enabled modules WITH UUID
                            foreach (($data['modules'] ?? []) as $moduleId) {
                                MerchantPermissionModule::create([
                                    'id' => Str::uuid(),
                                    'merchant_id' => $record->id,
                                    'permission_module_id' => $moduleId,
                                ]);
                            }
                        });
                    }),


        Action::make('settings')
                    ->label('')
                    ->icon('heroicon-s-cog-6-tooth')
                    ->tooltip('Merchant Settings')
                    ->modalHeading('Merchant Settings')
                    ->modalSubmitActionLabel('Save Settings')
                    ->modalWidth('xl')

                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()
                        ?->hasPermissionTo('merchants.update', Filament::getCurrentPanel()->getAuthGuard())
                    )

                    // ================= FORM =================
                    ->form([
                        FileUpload::make('merchant_logo')
                            ->label('Merchant Logo')
                            ->image()
                            ->disk('public')
                            ->directory('merchants/logos')
                            ->imagePreviewHeight(120),

                        FileUpload::make('profile_photo')
                            ->label('Profile Photo')
                            ->image()
                            ->disk('public')
                            ->directory('merchants/profile-photos')
                            ->imagePreviewHeight(120),

                        Section::make('Light Mode Colors')
                            ->columnSpanFull()
                            ->columns(3)
                            ->schema([
                                ColorPicker::make('primary_color')
                                    ->label('Primary')
                                    ->required(),

                                ColorPicker::make('secondary_color')
                                    ->label('Secondary')
                                    ->required(),

                                ColorPicker::make('warning_color')
                                    ->label('Warning')
                                    ->required(),

                                ColorPicker::make('danger_color')
                                    ->label('Danger')
                                    ->required(),

                                ColorPicker::make('success_color')
                                    ->label('Success')
                                    ->required(),

                                ColorPicker::make('default_color')
                                    ->label('Default')
                                    ->required(),
                            ]),

                        Hidden::make('merchant_id'),
                    ])

                    // ================= FILL FORM (Action equivalent of mutate-before-fill) =================
                    ->fillForm(function (Merchant $record): array {
                        $settings = $record->settings;

                        return [
                            'merchant_id' => $record->id,

                            'primary_color'   => $settings?->primary_color,
                            'secondary_color' => $settings?->secondary_color,
                            'warning_color'   => $settings?->warning_color,
                            'danger_color'    => $settings?->danger_color,
                            'success_color'   => $settings?->success_color,
                            'default_color'   => $settings?->default_color,

                            'merchant_logo' => $record->logo?->photo_url
                                ? [$record->logo->photo_url]
                                : null,

                            'profile_photo' => $record->profilePhoto?->photo_url
                                ? [$record->profilePhoto->photo_url]
                                : null,
                        ];
                    })

                    // ================= SAVE (UPSERT) =================
                    ->action(function (array $data, Merchant $record) {

                        MerchantSetting::updateOrCreate(
                            ['merchant_id' => $record->id],
                            Arr::only($data, [
                                'primary_color',
                                'secondary_color',
                                'warning_color',
                                'danger_color',
                                'success_color',
                                'default_color',
                            ])
                        );

                        // PROFILE PHOTO
                        if (!empty($data['profile_photo'])) {
                            $record->profilePhoto()?->delete();

                            $record->profilePhoto()->create([
                                'merchant_id' => $record->id,
                                'type'        => AttachmentType::IMAGE,
                                'meta_type'   => AttachmentMetaType::PROFILE_PHOTO,
                                'photo_url'   => collect($data['profile_photo'])->first(),
                            ]);
                        }

                        // MERCHANT LOGO
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
            ])
            ->defaultSort('created_at', 'desc');
    }
}
