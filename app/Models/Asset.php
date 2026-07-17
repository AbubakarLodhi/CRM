<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\AttachmentMetaType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Asset extends Model implements Auditable
{
    use HasUuids;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'merchant_id',
        'business_id',
        'branch_id',
        'asset_type_id',
        'asset_code',
        'name',
        'description',
        'serial_number',
        'model_number',
        'manufacturer',
        'purchase_date',
        'purchase_cost',
        'current_value',
        'warranty_expiry',
        'status',
        'condition',
        'location',
        'assigned_to',
        'vendor_id',
        'notes',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_expiry' => 'date',
            'purchase_cost' => 'decimal:2',
            'current_value' => 'decimal:2',
            'status' => AssetStatus::class,
            'condition' => AssetCondition::class,
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachment(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('meta_type', AttachmentMetaType::ASSET_FILE)
            ->whereNull('deleted_at');
    }
}
