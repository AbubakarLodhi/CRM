<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use App\Enums\AttachmentMetaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Product extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'products';

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string $keyType */
    protected $keyType = 'string';

    /** @var string[] $fillable */
    protected $fillable = [
        'merchant_id',
        'business_id',
        'category_id',
        'sub_category_id',
        'brand_id',
        'brand_model_id',
        'name',
        'sku',
        'description',
        'purchase_price',
        'selling_price',
        'type',
        'unit',
        'track_inventory',
        'is_variable_price',
        'is_active',
    ];

    /** @var string[] $casts Attribute type casting */
    protected $casts = [
        'purchase_price'    => 'decimal:2',
        'selling_price'     => 'decimal:2',
        'is_active'         => 'boolean',
        'track_inventory'   => 'boolean',
        'is_variable_price' => 'boolean',
    ];

    /* -------------------------
     | Relationships
     |--------------------------*/

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
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(
            Business::class,
            'business_products',
        )
            ->using(\App\Models\BusinessProduct::class)
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(
            Branch::class,
            'branch_products',
        )
            ->using(\App\Models\BranchProduct::class)
            ->withTimestamps();
    }


    /**
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * @return BelongsTo
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    /**
     * @return BelongsTo
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo
     */
    public function brandModel(): BelongsTo
    {
        return $this->belongsTo(BrandModel::class);
    }

    /**
     * @return MorphOne
     */
    public function productImage(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('meta_type', AttachmentMetaType::PRODUCT_IMAGE)
            ->whereNull('deleted_at');
    }
    /* -------------------------
     | Variant System
     |--------------------------*/

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
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Product $product): void {
            if ($product->isForceDeleting()) {
                $product->variants()
                    ->withTrashed()
                    ->get()
                    ->each(fn (ProductVariant $variant) => $variant->forceDelete());

                return;
            }

            $product->variants()->delete();
        });

        static::restoring(function (Product $product): void {
            $product->variants()->onlyTrashed()->restore();
        });
    }
}
