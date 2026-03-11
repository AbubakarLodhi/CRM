<?php

namespace App\Models;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;
    use SoftDeletes;

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string[] $fillable */
    protected $fillable = [
        'merchant_id', 'vendor_id', 'purchase_no', 'purchase_date', 'subtotal',
        'total_amount', 'paid_amount', 'due_amount', 'notes', 'created_by', 'payment_type',
    ];

    /** @var string $keyType */
    protected $keyType = 'string';

    /** @var string[] $casts */
    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'payment_type' => 'string',
    ];

    /**
     * @return BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    public function activeVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }


    /**
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $purchase): void {
            if ($purchase->isForceDeleting()) {
                $purchase->returns()
                    ->withTrashed()
                    ->with('items.variants')
                    ->get()
                    ->each(fn (PurchaseReturn $return): bool|null => $return->forceDelete());

                return;
            }

            $purchase->returns()->get()->each->delete();
        });

        static::restoring(function (self $purchase): void {
            $purchase->returns()->onlyTrashed()->get()->each->restore();
        });
    }
}
