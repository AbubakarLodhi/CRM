<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasUuids;

    /** @var string[] $fillable */
    protected $fillable = ['merchant_id', 'business_id', 'branch_id', 'customer_id', 'sale_no', 'sale_date', 'subtotal', 'discount', 'tax', 'total_amount', 'notes', 'created_by'];

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string $keyType */
    protected $keyType = 'string';

    /**
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
