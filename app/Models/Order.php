<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUuids;

    /** @var string[] $fillable */
    protected $fillable = ['merchant_id', 'business_id', 'branch_id', 'sale_id', 'status', 'status_notes'];

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string $keyType */
    protected $keyType = 'string';

    /**
     * @return BelongsTo
     */
    public function sale():BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
