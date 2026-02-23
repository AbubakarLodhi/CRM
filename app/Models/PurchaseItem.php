<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string[] $fillable */
    protected $fillable = [
        'purchase_id',
        'business_id',   // ✅ NEW
        'branch_id',     // ✅ NEW
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
        'discount',
        'tax',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @var string $keyType */
    protected $keyType = 'string';

    /** @var bool $timestamps */
    public $timestamps = false;

    /** @var string[] $casts */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
    ];

    /**
     * @return BelongsTo
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variants()
    {
        return $this->hasMany(PurchaseItemVariant::class);
    }

}
