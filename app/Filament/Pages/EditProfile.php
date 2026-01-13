<?php

namespace App\Filament\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use function PHPUnit\Framework\isInstanceOf;

class EditProfile extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    // 🔴 IMPORTANT: Page MUST have a view
    protected string $view = 'filament.pages.edit-profile';

    public array $data = [];

    public static function getLabel(): string
    {
        return 'Edit Profile';
    }

    public function getTitle(): string
    {
        return 'Edit Profile';
    }

    /**
     * Pre-fill form (including existing profile photo)
     */
    public function mount(): void
    {
        $merchant = Auth::guard('merchant')->user();

        $this->data = [
            'name' => $merchant->name,
            'email' => $merchant->email,
            'profile_photo' => $merchant->profilePhoto
                ? [$merchant->profilePhoto->photo_url]
                : null,
        ];
    }

    /**
     * Filament v4 form schema
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->disabled()
                    ->dehydrated(false),

                FileUpload::make('profile_photo')
                    ->label('Profile Photo')
                    ->image()
                    ->disk('public')
                    ->directory('merchants/profile-photos')
                    ->imagePreviewHeight(120)
                    ->getUploadedFileNameForStorageUsing(function ($file) {
                        $ext = $file->getClientOriginalExtension();
                        return 'profile-photo-' . now()->format('YmdHis') . '.' . $ext;
                    }),
            ]);
    }

    /**
     * Save handler
     */

    public function save(): void
    {
        $merchant = Auth::guard('merchant')->user();

        $merchant->update([
            'name' => $this->data['name'],
        ]);

        $uploaded = collect($this->data['profile_photo'] ?? []);

        if ($uploaded->isNotEmpty()) {
            $file = $uploaded->first();

            if ($file instanceof TemporaryUploadedFile) {
                $path = $file->store('merchants/profile-photos', 'public');
                $merchant->profilePhoto()?->delete();

                $merchant->profilePhoto()->create([
                    'merchant_id' => $merchant->id,
                    'type'        => AttachmentType::IMAGE,
                    'meta_type'   => AttachmentMetaType::PROFILE_PHOTO,
                    'photo_url'   => $path,
                ]);
            }
        } else {
            $merchant->profilePhoto()?->delete();
        }

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();

        $this->redirect(
            Filament::getCurrentPanel()->getUrl()
        );

    }

}
