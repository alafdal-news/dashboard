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

            # Theme — synced with alafdal-frontend
            # Frontend primary: #811619 | secondary: #051635
            # Header/footer gradient: #0732b2 → #1a006b
            ->font('IBM Plex Sans Arabic')
            ->colors([
                'primary' => '#811619',
                'gray' => Color::Slate,
                'danger' => Color::Rose,
                'info' => [
                    50  => '#eef2ff',
                    100 => '#dbe4ff',
                    200 => '#bac8ff',
                    300 => '#91a7ff',
                    400 => '#5c7cfa',
                    500 => '#0732b2',
                    600 => '#1a006b',
                    700 => '#15005a',
                    800 => '#0f0044',
                    900 => '#0a002e',
                    950 => '#051635',
                ],
            ])
            ->brandName('الأفضل نيوز')
            ->brandLogo(asset('logo-afdal-news.png'))
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
                fn () => new HtmlString($this->customStyles())
            );
    }

    /**
     * Custom CSS that aligns the dashboard look-and-feel with the frontend.
     */
    private function customStyles(): string
    {
        return <<<'CSS'
        <style>
            /* ─── RTL for rich-editor ─── */
            .fi-fo-rich-editor-content {
                direction: rtl;
                text-align: right;
            }

            /* ─── Sidebar — matches frontend header gradient ─── */
            .fi-sidebar {
                background: linear-gradient(to bottom, #0732b2, #1a006b) !important;
                border-inline-end: none !important;
            }

            /* Sidebar header (logo area) */
            .fi-sidebar-header {
                border-bottom: 1px solid rgba(255,255,255,0.12) !important;
            }

            /* Brand name color in sidebar */
            .fi-sidebar-header a,
            .fi-sidebar-header span {
                color: #ffffff !important;
            }

            /* Sidebar nav items */
            .fi-sidebar-nav .fi-sidebar-item a,
            .fi-sidebar-nav .fi-sidebar-item button {
                color: rgba(255,255,255,0.85) !important;
                transition: all 0.15s ease;
            }

            .fi-sidebar-nav .fi-sidebar-item a:hover,
            .fi-sidebar-nav .fi-sidebar-item button:hover {
                background: rgba(255,255,255,0.12) !important;
                color: #ffffff !important;
            }

            /* Active sidebar item */
            .fi-sidebar-item-active > a,
            .fi-sidebar-item-active > button {
                background: rgba(255,255,255,0.18) !important;
                color: #ffffff !important;
            }

            /* Sidebar group labels */
            .fi-sidebar-group-label {
                color: rgba(255,255,255,0.55) !important;
                font-size: 0.7rem;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            /* Sidebar icons */
            .fi-sidebar-item-icon {
                color: rgba(255,255,255,0.7) !important;
            }
            .fi-sidebar-item-active .fi-sidebar-item-icon {
                color: #ffffff !important;
            }

            /* Sidebar badge (counts) */
            .fi-sidebar-item-badge {
                background: rgba(255,255,255,0.2) !important;
                color: #ffffff !important;
            }

            /* Sidebar collapse button */
            .fi-sidebar-close-btn,
            .fi-icon-btn-sidebar-collapse {
                color: rgba(255,255,255,0.7) !important;
            }
            .fi-sidebar-close-btn:hover,
            .fi-icon-btn-sidebar-collapse:hover {
                background: rgba(255,255,255,0.12) !important;
                color: #ffffff !important;
            }

            /* ─── Topbar (header) ─── */
            .fi-topbar {
                background: linear-gradient(to left, #0732b2, #1a006b) !important;
                border-bottom: none !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            }
            .fi-topbar nav {
                background: transparent !important;
            }
            .fi-topbar a,
            .fi-topbar button,
            .fi-topbar span {
                color: rgba(255,255,255,0.9) !important;
            }
            .fi-topbar a:hover,
            .fi-topbar button:hover {
                color: #ffffff !important;
            }

            /* User menu avatar ring */
            .fi-avatar {
                ring-color: rgba(255,255,255,0.3);
            }

            /* ─── Login page ─── */
            .fi-simple-layout {
                background: linear-gradient(135deg, #0732b2 0%, #1a006b 60%, #051635 100%) !important;
            }
            .fi-simple-main-ctn {
                background: rgba(255,255,255,0.97) !important;
                backdrop-filter: blur(12px);
                border: 1px solid rgba(255,255,255,0.2) !important;
                box-shadow: 0 12px 40px rgba(0,0,0,0.25) !important;
                border-radius: 1rem !important;
            }

            /* ─── Custom scrollbar (matches frontend) ─── */
            ::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            ::-webkit-scrollbar-track {
                background: #efefef;
            }
            ::-webkit-scrollbar-thumb {
                background: #811619;
                border-radius: 4px;
            }
            ::-webkit-scrollbar-thumb:hover {
                background: #051635;
            }

            /* ─── Accent touches ─── */
            /* Section borders use primary */
            .fi-section {
                border-color: rgba(129,22,25,0.12) !important;
            }

            /* Stat cards subtle top border accent */
            .fi-wi-stats-overview-stat {
                border-top: 3px solid #811619 !important;
                border-radius: 0.5rem !important;
            }

            /* Buttons — primary bg matches frontend #811619 */
            .fi-btn-primary {
                background-color: #811619 !important;
            }
            .fi-btn-primary:hover {
                background-color: #6b1214 !important;
            }
        </style>
CSS;
    }
}
