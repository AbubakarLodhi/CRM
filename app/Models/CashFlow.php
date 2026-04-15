<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashFlow extends Model
{
    use HasUuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'merchant_id',
        'party_type',
        'party_id',
        'settlement_for_id',
        'flow_type',
        'direction',
        'amount',
        'flow_date',
        'method',
        'reference_no',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'flow_date' => 'date',
    ];

    public static function flowTypeLabels(): array
    {
        return [
            'advance' => 'Account Payable',
            'loan' => 'Account Receivable',
        ];
    }

    public static function flowTypeLabel(?string $flowType, string $default = '-'): string
    {
        return self::flowTypeLabels()[$flowType] ?? $default;
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function party(): MorphTo
    {
        return $this->morphTo();
    }

    public function settlementFor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'settlement_for_id');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(self::class, 'settlement_for_id');
    }

    public function expectedPrimaryDirection(): string
    {
        if ($this->party_type === Customer::class) {
            return $this->flow_type === 'loan' ? 'out' : 'in';
        }

        if ($this->party_type === Vendor::class) {
            return $this->flow_type === 'loan' ? 'in' : 'out';
        }

        return 'in';
    }

    public function isPrimaryTransaction(): bool
    {
        return $this->settlement_for_id === null && $this->direction === $this->expectedPrimaryDirection();
    }
}
