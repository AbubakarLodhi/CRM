<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use App\Enums\AttachmentMetaType;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class Merchant extends Authenticatable implements HasAvatar , CanResetPasswordContract, Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids, HasRoles,Notifiable,CanResetPassword;

    /** @var string */
    const STATUS_PENDING = 'pending';

    /** @var string */
    const STATUS_VERIFIED = 'verified';

    /** @var string */
    const STATUS_REJECTED = 'rejected';

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string $keyType */
    protected $keyType = 'string';

    /** @var string[] $hidden */
    protected $hidden = ['password'];

    /** @var string[] $fillable */
    protected $fillable = [
        'name', 'phone', 'password', 'email', 'status', 'address_line_1', 'address_line_2', 'city',
        'social_media_handles', 'website', 'is_active',
        'whatsapp_number', 'ntn_number', 'extra_fields',
        'cash_in_hand', 'cash_in_bank',
    ];

    protected $casts = [
        'extra_fields' => 'array',
        'cash_in_hand' => 'decimal:2',
        'cash_in_bank' => 'decimal:2',
    ];

    /**
     * @return string[]
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_VERIFIED,
            self::STATUS_REJECTED,
        ];
    }

    /**
     * @return HasOne
     */
    public function settings(): HasOne
    {
        return $this->hasOne(MerchantSetting::class);
    }

    /**
     * @return HasMany
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    /**
     * @return HasMany
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @return HasMany
     */
    public function brandModel(): HasMany
    {
        return $this->hasMany(BrandModel::class);
    }

    /**
     * @return HasMany
     */
    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    /**
     * @return HasMany
     */
    public function Addons(): HasMany
    {
        return $this->hasMany(AddOn::class);
    }

    /**
     * @return HasMany
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return MorphMany
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @return MorphOne
     */
    public function logo(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('meta_type', AttachmentMetaType::MERCHANT_LOGO);
    }

    /**
     * @return MorphOne
     */
    public function profilePhoto(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('meta_type', AttachmentMetaType::PROFILE_PHOTO);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $attachment = $this->profilePhoto;

        if (! $attachment?->photo_url) {
            return null;
        }

        return Storage::disk('public')->url($attachment->photo_url);
    }

    public function permissionModules()
    {
        return $this->belongsToMany(
            PermissionModule::class,
            'merchant_permission_modules'
        )->withTimestamps();
    }

}
