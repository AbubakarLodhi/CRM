<?php

namespace App\Providers;

use App\Auth\Http\Responses\LoginResponse;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            config([
                'session.secure' => env('SESSION_SECURE_COOKIE', true),
            ]);
        }
        Table::configureUsing(function (Table $table): void {
            $table
                ->filtersFormMaxHeight('28rem')
                ->columnManagerMaxHeight('28rem');
        });

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
