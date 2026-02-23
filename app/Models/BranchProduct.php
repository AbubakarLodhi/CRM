<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BranchProduct extends Pivot implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    //
    use HasUuids;

    protected $table = 'branch_products';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'branch_id',
        'product_id',
    ];
    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(product::class);
    }

    /**
     * @return BelongsTo
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
