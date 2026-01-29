<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleItem extends Model
{
    use HasUuids;

    /** @var bool */
    public $incrementing = false;

    /** @var string[] */
    protected $fillable = [
        'sale_id',
        'business_id',
        'branch_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'line_total',
    ];


    /** @var string */
    protected $keyType = 'string';

    /** @var bool */
    public $timestamps = false;

    /** @var string[] */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(SaleItemVariant::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

}
