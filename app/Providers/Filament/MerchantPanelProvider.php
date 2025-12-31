<?php

namespace App\Providers\Filament;

use Filament\Facades\Filament;
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
                if (!$merchant || !$merchant->logo) return null;

                $path = $merchant->logo->photo_url;

                if (!Storage::disk('public')->exists($path)) return null;
                return asset('storage/' . $path);
            })
            ->brandName(fn() => auth('merchant')->user()?->name ?? 'Sales_Crm')
            ->brandLogoHeight('2.5rem')
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
                    if (!$merchant || !$merchant->settings) return null;

                    return view('filament.merchant.theme-vars', [
                        'settings' => $merchant->settings,
                    ]);
                })
            ->colors([
                'primary' => Color::Blue,
                'warning' => Color::Yellow,
                'danger' => Color::Red,
                'default' => Color::Neutral,
                'secondary' => Color::Gray,

                /*---------------------------Light Mode------------------------------*/
//                'primary'   => '#1E3A8A', // Solar Blue
//                'success'   => '#22C55E', // Eco Green
//                'warning'   => '#FACC15', // Solar Yellow
//                'danger'    => '#DC2626', // Controlled Red
//                'secondary' => '#64748B', // Slate
//                'default'   => '#E5E7EB', // Soft Gray

                /*---------------------------Dark Mode------------------------------*/
//                'primary_dark'   => '#3B82F6', // brighter blue
//                'success_dark'   => '#4ADE80', // luminous green
//                'warning_dark'   => '#FDE047', // softer yellow
//                'danger_dark'    => '#F87171', // readable red
//                'secondary_dark' => '#94A3B8',
//                'default_dark'   => '#1F2937',
            ])
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

}
