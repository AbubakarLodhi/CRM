<?php

namespace App\Models;

use App\Support\NotificationTemplateChannels;
use App\Support\NotificationTemplateEvents;
use Illuminate\Database\Eloquent\Builder;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    protected $fillable = [
        'merchant_id',
        'events',
        'channels',
        'subject',
        'content',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'events' => 'array',
        'channels' => 'array',
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @param  Builder<NotificationTemplate>  $query
     * @return Builder<NotificationTemplate>
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->whereJsonContains('events', $event);
    }

    /**
     * @param  Builder<NotificationTemplate>  $query
     * @return Builder<NotificationTemplate>
     */
    public function scopeForChannel(Builder $query, string $channel): Builder
    {
        return $query->whereJsonContains('channels', $channel);
    }

    public function appliesToEvent(string $event): bool
    {
        return in_array($event, $this->events ?? [], true);
    }

    public function appliesToChannel(string $channel): bool
    {
        return in_array($channel, $this->channels ?? [], true);
    }

    /**
     * @return list<string>
     */
    public function eventLabels(): array
    {
        return collect($this->events ?? [])
            ->map(fn (string $event) => NotificationTemplateEvents::label($event))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function channelLabels(): array
    {
        return collect($this->channels ?? [])
            ->map(fn (string $channel) => NotificationTemplateChannels::label($channel))
            ->values()
            ->all();
    }
}
