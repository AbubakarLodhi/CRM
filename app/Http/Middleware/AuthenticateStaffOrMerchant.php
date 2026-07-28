<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateStaffOrMerchant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('staff')->check() || auth('merchant')->check()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $referer = (string) $request->headers->get('referer', '');
        $loginRoute = str_contains($referer, '/staff')
            ? route('filament.user.auth.login')
            : route('filament.merchant.auth.login');

        return redirect()->guest($loginRoute);
    }
}
