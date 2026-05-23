<?php

namespace App\Support;

use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Builder;

class NotificationTemplateResolver
{
    public static function resolve(
        string $event,
        ?string $merchantId,
        ?NotificationTemplate $preferred = null,
    ): ?NotificationTemplate {
        if (
            $preferred
            && $preferred->is_active
            && $preferred->appliesToEvent($event)
        ) {
            return $preferred;
        }

        return self::baseQuery($merchantId)
            ->forEvent($event)
            ->orderByRaw('merchant_id is null')
            ->latest('updated_at')
            ->first();
    }

    /**
     * @param  Builder<NotificationTemplate>  $query
     * @return Builder<NotificationTemplate>
     */
    public static function baseQuery(?string $merchantId): Builder
    {
        return NotificationTemplate::query()
            ->where('is_active', true)
            ->where(function ($query) use ($merchantId) {
                $query->where('merchant_id', $merchantId)
                    ->orWhereNull('merchant_id');
            });
    }
}
