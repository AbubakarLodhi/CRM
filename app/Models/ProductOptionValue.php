<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOptionValue extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    protected $fillable = [
        'product_option_id',
        'value',
    ];

    /* -------------------------
     | Relationships
     |--------------------------*/

    /**
     * @return BelongsTo
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    /**
     * @return HasMany
     */
    public function variantValues(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class);
    }
}
