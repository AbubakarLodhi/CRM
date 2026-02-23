<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOption extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    /** @var string[] $fillable */
    protected $fillable = [
        'product_id',
        'name',
        'display_name',
    ];

    /* -------------------------
     | Relationships
     |--------------------------*/

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class);
    }
}
