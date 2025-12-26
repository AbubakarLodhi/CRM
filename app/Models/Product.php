<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasUuids;

    protected $table = 'products';

    public $incrementing = false;
    protected $keyType = 'string';

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

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function brandModel(): BelongsTo
    {
        return $this->belongsTo(BrandModel::class);
    }

    /* -------------------------
     | Variant System
     |--------------------------*/

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }
}
