<?php

namespace App\Auditing\Resolvers;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Contracts\UserResolver as Resolver;

class UserResolver implements Resolver
{
    /**
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public static function resolve()
    {
        try {
            $filamentUser = Filament::auth()->user();

            if ($filamentUser) {
                return $filamentUser;
            }
        } catch (\Throwable $exception) {
            // Fallback to guard-based resolver logic below.
        }

        $guards = Config::get('audit.user.guards', [
            Config::get('auth.defaults.guard'),
        ]);

        foreach ($guards as $guard) {
            try {
                if (Auth::guard($guard)->check()) {
                    return Auth::guard($guard)->user();
                }
            } catch (\Throwable $exception) {
                continue;
            }
        }

        return null;
    }
}

