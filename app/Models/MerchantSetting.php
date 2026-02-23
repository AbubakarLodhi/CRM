<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantSetting extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string[] $fillable */
    protected $fillable = [
        'merchant_id', 'primary_color', 'secondary_color', 'warning_color', 'danger_color', 'success_color',
        'default_color', 'invoice_header_groups', 'invoice_footer_groups',
    ];

    protected $casts = [
        'invoice_header_groups' => 'array',
        'invoice_footer_groups' => 'array',
    ];

    /** @var string $keyType */
    protected $keyType = 'string';

    /**
     * @return BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
