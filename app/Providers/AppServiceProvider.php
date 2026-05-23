<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Filament::serving(function () {

            $panelId = Filament::getCurrentPanel()?->getId();

            if ($panelId === 'admin') {
                config([
                    'auth.defaults.guard' => 'admin',
                    'auth.defaults.passwords' => 'admins',
                ]);
            }

            if ($panelId === 'merchant') {
                config([
                    'auth.defaults.guard' => 'merchant',
                    'auth.defaults.passwords' => 'merchants',
                ]);
            }

            if ($panelId === 'user') {
                config([
                    'auth.defaults.guard' => 'staff',
                    'auth.defaults.passwords' => 'staffs',
                ]);
            }
        });
    }
}
