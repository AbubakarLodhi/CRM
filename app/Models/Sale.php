<?php

namespace App\Models;

use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sale extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;
    use SoftDeletes;

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string[] $fillable */
    protected $fillable = [
        'merchant_id', 'customer_id', 'sale_no', 'sale_date', 'subtotal',
        'total_amount', 'paid_amount', 'due_amount', 'notes', 'created_by', 'payment_type',
    ];

    /** @var string $keyType */
    protected $keyType = 'string';

    /** @var string[] $casts */
    protected $casts = [
        'sale_date' => 'date',
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




    /**
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function activeCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
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
        return $this->hasMany(SaleItem::class);
    }

    /**
     * @return HasMany
     */
    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $sale): void {
            if ($sale->isForceDeleting()) {
                $sale->returns()
                    ->withTrashed()
                    ->with('items.variants')
                    ->get()
                    ->each(fn (SaleReturn $return): bool|null => $return->forceDelete());

                return;
            }

            $sale->returns()->get()->each->delete();
        });

        static::restoring(function (self $sale): void {
            $sale->returns()->onlyTrashed()->get()->each->restore();
        });
    }

}
