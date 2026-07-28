<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Http\Middleware\AuthenticateSession as FilamentAuthenticateSession;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

class AuthenticatePanelSession extends FilamentAuthenticateSession
{
    public function __construct(AuthFactory $auth)
    {
        parent::__construct($auth);
    }

    protected function guard()
    {
        $panel = Filament::getCurrentPanel();

        if ($panel !== null) {
            return $this->auth->guard($panel->getAuthGuard());
        }

        return parent::guard();
    }
}
