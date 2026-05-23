<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class InvoiceDynamicGroup extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string $keyType */
    protected $keyType = 'string';

    /** @var string[] $fillable */
    protected $fillable = [
        'merchant_id',
        'section',
        'name',
        'is_active',
    ];

    /** @var string[] $casts */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(InvoiceDynamicField::class, 'invoice_dynamic_group_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }
}
