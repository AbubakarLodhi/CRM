<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Business extends Model
{
    use HasUuids;

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string[] $fillable */
    protected $fillable = [
        'merchant_id', 'name', 'description', 'status', 'country_id', 'city_id', 'postal_code',
    ];

    /** @var string $keyType */
    protected $keyType = 'string';

    protected static function booted()
    {
        static::deleting(function (Business $business) {
            $business->users()->detach();

            $business->branches()->delete();
        });
    }


    /**
     * @return BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * @return HasMany
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * @return BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'business_products',
        )
            ->using(\App\Models\BusinessProduct::class)
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

    /**
     * @return MorphOne
     */
    public function logo(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('meta_type', \App\Enums\AttachmentMetaType::BUSINESS_LOGO);
    }

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'business_users'
        )->using(\App\Models\BusinessUser::class)
            ->withTimestamps();
    }

    public function countries():belongsToMany
    {
        return $this->belongsToMany(Country::class, 'business_country');
    }

    public function cities():belongsToMany
    {
        return $this->belongsToMany(City::class, 'business_city');
    }

}
