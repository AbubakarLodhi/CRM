<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    protected $fillable = [
        'merchant_id',
        'event',
        'channel',
        'subject',
        'content',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

}
