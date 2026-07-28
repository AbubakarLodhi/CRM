<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPanelAuthDefaults
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('merchant', 'merchant/*')) {
            config([
                'auth.defaults.guard' => 'merchant',
                'auth.defaults.passwords' => 'merchants',
            ]);
        } elseif ($request->is('staff', 'staff/*')) {
            config([
                'auth.defaults.guard' => 'staff',
                'auth.defaults.passwords' => 'staffs',
            ]);
        }

        return $next($request);
    }
}
