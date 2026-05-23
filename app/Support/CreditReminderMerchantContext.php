<?php

namespace App\Support;

use Filament\Facades\Filament;

class CreditReminderMerchantContext
{
    public static function resolveMerchantId(): ?string
    {
        $user = Filament::auth()->user();

        return match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User => $user->merchant_id,
            default => null,
        };
    }
}
