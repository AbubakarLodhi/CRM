<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoVisitorSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'visitor_hash',
        'merchant_id',
        'ip_address',
        'started_at',
        'expires_at',
        'last_seen_at',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function remainingSeconds(): int
    {
        return max(0, (int) now()->diffInSeconds($this->expires_at, false));
    }

    public function touchLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
