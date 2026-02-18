<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\ArticlesChart;
use App\Filament\Widgets\QuickLinks;
use App\Filament\Widgets\StatsOverview;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login()
            ->spa()

            # Theme customization
            ->colors([
                'primary' => "#931e0e",
                'gray' => Color::Slate,
            ])
            ->brandName('Alafdal News')
            ->brandLogo(asset('logo.jpg'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('favicon.ico'))
            ->breadcrumbs(false)

            ->unsavedChangesAlerts()
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->widgets([
                QuickLinks::class,
                StatsOverview::class,
                ArticlesChart::class,
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
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => new HtmlString('
                    <style>
                        .fi-fo-rich-editor-content {
                            direction: rtl;
                            text-align: right;
                        }
                    </style>
                ')
            );
    }
}
