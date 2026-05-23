<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class EnsureStaffIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if (
            $user->status !== User::STATUS_VERIFIED ||
            ! $user->is_active
        ) {
            Filament::auth()->logout();

            $message = match ($user->status) {
                User::STATUS_PENDING  => 'Your account is pending approval.',
                User::STATUS_REJECTED => 'Your account has been rejected. Please contact the administrator.',
                default               => 'Your account is inactive.',
            };

            return redirect()
                ->to(Filament::getLoginUrl())
                ->withErrors(['data.email' => $message]);
        }

        return $next($request);
    }
}
