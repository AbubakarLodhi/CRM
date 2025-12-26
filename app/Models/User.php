<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, HasRoles;

    /** @var string $keyType */
    protected $keyType = 'string';

    /** @var string */
    const STATUS_PENDING = 'pending';

    /** @var string */
    const STATUS_VERIFIED = 'verified';

    /** @var string */
    const STATUS_REJECTED = 'rejected';

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string $guard_name */
    protected $guard_name = 'merchant';

    /** @var string[] $fillable */
    protected $fillable = ['name', 'email', 'merchant_id', 'password', 'status', 'is_active'];

    /** @var string[] $hidden */
    protected $hidden = ['password', 'remember_token'];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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
     * @return BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * @return BelongsToMany
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

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
}
