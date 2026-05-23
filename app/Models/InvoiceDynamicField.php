<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class InvoiceDynamicField extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string $keyType */
    protected $keyType = 'string';

    /** @var string[] $fillable */
    protected $fillable = [
        'invoice_dynamic_group_id',
        'label',
        'value_type',
        'value_key',
        'static_value',
        'sort_order',
        'is_active',
    ];

    /** @var string[] $casts */
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(InvoiceDynamicGroup::class, 'invoice_dynamic_group_id');
    }
}
