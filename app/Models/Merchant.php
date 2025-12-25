<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Traits\HasRoles;

class Merchant extends Authenticatable
{
    use HasUuids;
    use HasRoles;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $hidden = ['password'];

    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';

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

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => filled($value) ? Hash::make($value) : null
        );
    }
    public function settings()
    {
        return $this->hasOne(MerchantSetting::class);
    }

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_VERIFIED,
            self::STATUS_REJECTED,
        ];
    }
}
