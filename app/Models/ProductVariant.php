<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProductVariant extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'product_variants';

    /** @var string[] $fillable */
    protected $fillable = [
        'merchant_id',
        'product_id',
        'name',
        'sku',
        'purchase_price',
        'selling_price',
        'is_active',
    ];

    /** @var string[] $casts Attribute type casting */
    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price'  => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
    public function values(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class);
    }

    /**
     * @return MorphMany
     */
    public function images():MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')
            ->where('meta_type', \App\Enums\AttachmentMetaType::VARIANT_IMAGE);
    }

}
