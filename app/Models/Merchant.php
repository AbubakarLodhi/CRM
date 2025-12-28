<?php

namespace App\Models;

use App\Enums\AttachmentMetaType;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Traits\HasRoles;

class Merchant extends Authenticatable
{
    use HasUuids, HasRoles;

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string $keyType */
    protected $keyType = 'string';

    /** @var string[] $hidden */
    protected $hidden = ['password'];

    const STATUS_PENDING = 'pending';

    const STATUS_VERIFIED = 'verified';

    const STATUS_REJECTED = 'rejected';

    /** @var string[] $fillable */
    protected $fillable = [
        'name',
        'phone',
        'password',
        'email',
        'status',
        'address_line_1',
        'address_line_2',
        'city',
        'social_media_handles',
        'website',
        'is_active',
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
     * @return Attribute
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn($value) => filled($value) ? Hash::make($value) : null
        );
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


    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function logo()
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('meta_type', AttachmentMetaType::MERCHANT_LOGO);
    }

    public function profilePhoto()
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('meta_type', AttachmentMetaType::PROFILE_PHOTO);
    }
}
