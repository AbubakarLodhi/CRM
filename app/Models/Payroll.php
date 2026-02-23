<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    public $incrementing = false;

    protected $fillable = [
        'merchant_id',
        'user_id',
        'payroll_no',
        'period_month',
        'period_year',
        'base_salary',
        'allowances',
        'deductions',
        'net_salary',
        'status',
        'payment_date',
        'notes',
        'created_by',
    ];

    protected $keyType = 'string';

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'base_salary' => 'decimal:2',
        'allowances' => 'array',
        'deductions' => 'array',
        'net_salary' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PAID,
            self::STATUS_CANCELLED,
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
