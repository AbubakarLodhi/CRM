<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'merchant_id',
        'purchase_id',
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

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $purchaseReturn): void {
            if ($purchaseReturn->isForceDeleting()) {
                $purchaseReturn->items()
                    ->withTrashed()
                    ->get()
                    ->each(fn (PurchaseReturnItem $item): bool|null => $item->forceDelete());

                return;
            }

            $purchaseReturn->items()->get()->each->delete();
        });

        static::restoring(function (self $purchaseReturn): void {
            $purchaseReturn->items()->onlyTrashed()->get()->each->restore();
        });
    }
}
