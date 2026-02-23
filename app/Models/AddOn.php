<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class AddOn extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    /** @var string[] $fillable */
    protected $fillable = ['merchant_id', 'brand_model_id', 'name', 'price'];

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string $keyType */
    protected $keyType = 'string';

    /**
     * @return BelongsTo
     */
    public function brandModel(): BelongsTo
    {
        return $this->belongsTo(BrandModel::class, 'brand_model_id');
    }

    /**
     * @return BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}

