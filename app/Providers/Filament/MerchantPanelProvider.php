<?php

namespace App\Providers\Filament;

use App\Filament\Pages\EditProfile;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\EnsureStaffIsVerified;
use Filament\Actions\Action;
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

class MerchantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {

        return $panel
            ->id('merchant')
            ->path('merchant')
            ->authGuard('merchant')
            ->authPasswordBroker('merchants')
            ->default()
            ->login(\App\Filament\Auth\Login::class)
            ->passwordReset()
            ->brandLogo(function () {
                $merchant = Filament::auth()->user();

                if (! $merchant) {
                    return asset('images/zgn-crm-logo.png');
                }

                if (! $merchant->logo) {
                    return asset('images/zgn-crm-logo.png');
                }

                $path = $merchant->logo->photo_url;

                if (! Storage::disk('public')->exists($path)) {
                    return asset('images/zgn-crm-logo.png');
                }

                return asset('storage/'.$path);
            })
            ->brandName(fn () => Filament::auth()->user()?->name ?? 'ZGN Green Pvt')
            ->brandLogoHeight('2.5rem')
            ->userMenuItems([
                Action::make('editProfile')
                    ->label('Edit profile')
                    ->icon('heroicon-o-user')
                    ->url(fn () => EditProfile::getUrl(panel: 'merchant'))
                    ->sort(-10), // appears above theme switcher & logout
            ])

            ->viteTheme('resources/css/filament/merchant/theme.css')
            ->renderHook(
                'panels::head.end',
                function () {
                    /*$merchant = auth('merchant')->user();

                    $settings = $merchant->settings;
                    return [
                        'primary' => $settings?->primary_color ?? '#6d28d9',
                        'secondary' => $settings?->secondary_color ?? '#9333ea',
                    ];*/

                    $merchant = Filament::auth()->user();
                    $settings = $merchant?->settings;

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
                })
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
                'panels::body.end',
                fn () => view('filament.sidebar-hover')
            )

            ->globalSearch(false);

    }
}
