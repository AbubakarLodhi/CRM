<?php

namespace App\Auth\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as Responsable;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        if ($request->hasSession()) {
            $request->session()->save();
        }

        return redirect()->intended(Filament::getUrl());
    }
}
