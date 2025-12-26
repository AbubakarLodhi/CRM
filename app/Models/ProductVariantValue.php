<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantValue extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_variant_id',
        'product_option_id',
        'product_option_value_id',
    ];

    /* -------------------------
     | Relationships
     |--------------------------*/

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(ProductOptionValue::class, 'product_option_value_id');
    }
}
