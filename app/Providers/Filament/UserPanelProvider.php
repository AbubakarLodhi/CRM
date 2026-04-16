<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Http\Middleware\EnsureStaffIsVerified;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('user')
            ->path('staff')
            ->authGuard('staff')
            ->authPasswordBroker('staffs')
            ->login(\App\Filament\Auth\Login::class)
            ->passwordReset()
            ->colors([
                'primary' => Color::Green,
            ])
            ->brandLogo(function () {
                $path = Filament::auth()->user()?->merchant?->logo?->photo_url;

                if ($path && Storage::disk('public')->exists($path)) {
                    return asset('storage/'.$path);
                }

                return asset('images/zgn-crm-logo.png');
            })
            ->brandName(fn () => Filament::auth()->user()?->name ?? 'ZGN Green Pvt')
            ->viteTheme('resources/css/filament/merchant/theme.css')
            ->navigationGroups([
                'Procurement',
                'Inventory',
                'Reportings',
                'Configurations',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnsureStaffIsVerified::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->globalSearch(false);
    }
}
