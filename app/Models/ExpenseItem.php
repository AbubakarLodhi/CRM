<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseItem extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    /** @var bool */
    public $incrementing = false;

    /** @var string[] */
    protected $fillable = ['expense_id', 'description', 'quantity', 'unit_price', 'line_total'];

    /** @var string */
    protected $keyType = 'string';

    /** @var bool */
    public $timestamps = false;

    /** @var string[] */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
