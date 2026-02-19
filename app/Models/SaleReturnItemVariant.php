<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItemVariant extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'sale_return_item_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function saleReturnItem(): BelongsTo
    {
        return $this->belongsTo(SaleReturnItem::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
