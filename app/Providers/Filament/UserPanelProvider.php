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

            ->renderHook(
                'panels::head.end',
                function () {
                    $user = Filament::auth()->user();
                    $settings = $user?->merchant?->settings;

                    return view('filament.merchant.theme-vars', [
                        'primary' => Color::generatePalette($settings?->primary_color ?? '#1E3A8A'),
                        'success' => Color::generatePalette($settings?->success_color ?? '#22C55E'),
                        'secondary' => Color::generatePalette($settings?->secondary_color ?? '#64748B'),
                        'danger' => Color::generatePalette($settings?->danger_color ?? '#DC2626'),
                        'warning' => Color::generatePalette($settings?->warning_color ?? '#FACC15'),
                        'default' => Color::generatePalette($settings?->default_color ?? '#E5E7EB'),
                        'sidebarPrimary' => $settings?->primary_color,
                        'sidebarSecondary' => $settings?->secondary_color,
                    ]);
                }
            )
            ->renderHook(
                'panels::body.end',
                fn () => view('filament.sidebar-hover')
            )

            ->globalSearch(false);
    }
}
