<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BusinessProduct extends Pivot
{
    //
    use HasUuids;

    protected $table = 'business_products';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'business_id',
        'product_id',
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(product::class);
    }

    /**
     * @return BelongsTo
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
