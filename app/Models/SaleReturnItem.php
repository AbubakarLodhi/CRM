<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturnItem extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'business_id',
        'branch_id',
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
        'discount',
        'tax',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variants(): HasMany
    {
        return $this->hasMany(SaleReturnItemVariant::class);
    }
}
