<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'merchant_id',
        'sale_id',
        'customer_id',
        'return_no',
        'return_date',
        'subtotal',
        'total_discount',
        'total_tax',
        'total_amount',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
