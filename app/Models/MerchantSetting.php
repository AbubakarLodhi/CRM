<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantSetting extends Model
{
    use HasUuids;

    /** @var string[] $fillable */
    protected $fillable = ['merchant_id','logo_path','primary_color','secondary_color','currency','timezone'];

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string $keyType */
    protected $keyType = 'string';

    /**
     * @return BelongsTo
     */
    public function merchant():BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
