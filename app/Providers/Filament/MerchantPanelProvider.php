<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
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
            ->login()
            ->brandLogo(function () {
                $merchant = auth('merchant')->user();

                if (! $merchant || ! $merchant->logo) {
                    return null;
                }

                $path = $merchant->logo->photo_url;

                if (! \Storage::disk('public')->exists($path)) {
                    return null;
                }
                return asset('storage/' . $path);
            })


            ->brandName(fn () => auth('merchant')->user()?->name ?? 'Sales_Crm')

            ->brandLogoHeight('2.5rem')


            ->colors(fn () => $this->getMerchantColors())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('merchant');
    }
    protected function getMerchantColors(): array
    {
        try {
            $merchant = auth('merchant')->user();

            if (! $merchant) {
                return [
                    'primary' => 'oklch(0.809 0.105 251.813)', // fallback purple
                    'secondary' => '#9333ea',
                ];
            }

            $settings = $merchant->settings; // relationship
            return [
                'primary' => $settings?->primary_color ?? '#6d28d9',
                'secondary' => $settings?->secondary_color ?? '#9333ea',
            ];
        } catch (\Throwable $e) {
            return [
                'primary' => '#6d28d9',
                'secondary' => '#9333ea',
            ];
        }
    }

}
