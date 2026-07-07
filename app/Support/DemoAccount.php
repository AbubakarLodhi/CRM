<?php

namespace App\Support;

use App\Models\DemoVisitorSession;
use App\Models\Merchant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoAccount
{
    public static function email(): string
    {
        return (string) config('demo.email');
    }

    public static function temporaryEmailDomain(): string
    {
        return (string) config('demo.temporary_email_domain', 'crmdemo.com');
    }

    public static function temporaryEmailForSession(string $sessionId): string
    {
        $localPart = 'demo-'.str_replace('-', '', $sessionId);

        return $localPart.'@'.self::temporaryEmailDomain();
    }

    public static function isTemporaryDemoEmail(?string $email): bool
    {
        if ($email === null || $email === '') {
            return false;
        }

        $domain = preg_quote(self::temporaryEmailDomain(), '/');

        return (bool) preg_match('/^demo-[a-f0-9]{32}@'.$domain.'$/i', $email);
    }

    public static function isDemoMerchant(?Merchant $merchant = null): bool
    {
        $merchant ??= Auth::guard('merchant')->user();

        if (! $merchant instanceof Merchant) {
            return false;
        }

        return self::isTemporaryDemoEmail($merchant->email);
    }

    public static function visitorFingerprint(?Request $request = null): string
    {
        $request ??= request();

        $ip = (string) $request->ip();
        $userAgent = (string) $request->userAgent();

        return hash('sha256', $ip.'|'.$userAgent);
    }

    public static function findVisitorSession(?Request $request = null): ?DemoVisitorSession
    {
        return DemoVisitorSession::query()
            ->where('visitor_hash', self::visitorFingerprint($request))
            ->first();
    }

    public static function currentVisitorSession(): ?DemoVisitorSession
    {
        $sessionId = session('demo_visitor_session_id');

        if ($sessionId) {
            $record = DemoVisitorSession::query()->find($sessionId);

            if ($record) {
                return $record;
            }
        }

        return self::findVisitorSession();
    }

    public static function isDemoSession(): bool
    {
        return self::isDemoMerchant() && self::currentVisitorSession() !== null;
    }

    public static function remainingSeconds(): int
    {
        $visitorSession = self::currentVisitorSession();

        if (! $visitorSession) {
            return 0;
        }

        return $visitorSession->remainingSeconds();
    }

    public static function shouldBlockMutations(): bool
    {
        return false;
    }

    public static function abortIfDemoMutation(): void
    {
        //
    }

    public static function sessionTimeoutMinutes(): int
    {
        return max(1, (int) config('demo.session_timeout_minutes', 30));
    }

    public static function sessionTimeoutSeconds(): int
    {
        return self::sessionTimeoutMinutes() * 60;
    }

    public static function dailyResetAt(): string
    {
        return (string) config('demo.daily_reset_at', '17:00');
    }

    public static function lastDailyResetAt(): Carbon
    {
        $resetAt = self::dailyResetAt();
        $todayReset = now()->copy()->setTimeFromTimeString($resetAt);

        if (now()->greaterThanOrEqualTo($todayReset)) {
            return $todayReset;
        }

        return $todayReset->subDay();
    }

    public static function nextDailyResetAt(): Carbon
    {
        $resetAt = self::dailyResetAt();
        $todayReset = now()->copy()->setTimeFromTimeString($resetAt);

        if (now()->lessThan($todayReset)) {
            return $todayReset;
        }

        return $todayReset->addDay();
    }

    public static function dailyResetLabel(): string
    {
        return Carbon::today()
            ->setTimeFromTimeString(self::dailyResetAt())
            ->format('g:i A');
    }

    public static function canStartFreshDemoAfterReset(DemoVisitorSession $session): bool
    {
        if (! $session->isExpired()) {
            return false;
        }

        return $session->expires_at->lessThan(self::lastDailyResetAt());
    }
}
