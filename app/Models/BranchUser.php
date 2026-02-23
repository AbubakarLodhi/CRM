<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BranchUser extends Pivot implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    protected $table = 'branch_users';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'user_id',
        'branch_id',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
