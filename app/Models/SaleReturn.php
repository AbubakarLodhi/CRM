<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;
    use SoftDeletes;

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

    protected static function booted(): void
    {
        static::deleting(function (self $saleReturn): void {
            if ($saleReturn->isForceDeleting()) {
                $saleReturn->items()
                    ->withTrashed()
                    ->get()
                    ->each(fn (SaleReturnItem $item): bool|null => $item->forceDelete());

                return;
            }

            $saleReturn->items()->get()->each->delete();
        });

        static::restoring(function (self $saleReturn): void {
            $saleReturn->items()->onlyTrashed()->get()->each->restore();
        });
    }
}
