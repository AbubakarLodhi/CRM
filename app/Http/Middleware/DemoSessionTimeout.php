<?php

namespace App\Http\Middleware;

use App\Services\Demo\TemporaryDemoCleanup;
use App\Support\DemoAccount;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DemoSessionTimeout
{
    public function __construct(private TemporaryDemoCleanup $cleanup) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! DemoAccount::isDemoMerchant()) {
            return $next($request);
        }

        $visitorSession = DemoAccount::currentVisitorSession();

        if (! $visitorSession) {
            $visitorSession = DemoAccount::findVisitorSession($request);

            if ($visitorSession) {
                session(['demo_visitor_session_id' => $visitorSession->id]);
            }
        }

        if (! $visitorSession || $visitorSession->isExpired()) {
            if ($visitorSession) {
                $this->cleanup->purgeExpiredSession($visitorSession);
            }

            Auth::guard('merchant')->logout();
            $request->session()->forget('demo_visitor_session_id');

            return redirect()
                ->route('landing', ['notice' => 'demo_expired'])
                ->with('demo_expired', (string) config('demo.notices.demo_expired'));
        }

        $visitorSession->touchLastSeen();

        return $next($request);
    }
}
