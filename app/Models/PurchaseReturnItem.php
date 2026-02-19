<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturnItem extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'purchase_return_id',
        'purchase_item_id',
        'business_id',
        'branch_id',
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
        'discount',
        'tax',
    ];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
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

    public function variants(): HasMany
    {
        return $this->hasMany(PurchaseReturnItemVariant::class);
    }
}
