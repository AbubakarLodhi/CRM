<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasUuids;

    /** @var string */
    const STATUS_PENDING = 'pending';

    /** @var string */
    const STATUS_VERIFIED = 'verified';

    /** @var string */
    const STATUS_REJECTED = 'rejected';

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string[] $fillable */
    protected $fillable = [
        'merchant_id', 'business_id', 'name', 'address', 'phone', 'status', 'country_id', 'city_id', 'postal_code',
    ];

    /** @var string $keyType */
    protected $keyType = 'string';

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
     * @return BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'branch_products',
        )
            ->using(\App\Models\BranchProduct::class)
            ->withTimestamps();
    }
    /**
     * @return BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * @return BelongsTo
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'branch_users',
        )
            ->using(\App\Models\BranchUser::class)
            ->withTimestamps();
    }


    /**
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

}
