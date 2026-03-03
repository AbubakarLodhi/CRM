<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturnItem extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'business_id',
        'branch_id',
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
        'discount',
        'tax',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class)->withTrashed();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variants(): HasMany
    {
        return $this->hasMany(SaleReturnItemVariant::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $item): void {
            if ($item->isForceDeleting()) {
                $item->variants()
                    ->withTrashed()
                    ->get()
                    ->each(fn (SaleReturnItemVariant $variant): bool|null => $variant->forceDelete());

                return;
            }

            $item->variants()->get()->each->delete();
        });

        static::restoring(function (self $item): void {
            $item->variants()->onlyTrashed()->get()->each->restore();
        });
    }
}
