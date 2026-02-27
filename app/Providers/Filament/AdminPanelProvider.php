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

            /* ─── Sidebar — matches frontend header gradient (#0732b2 → #1a006b) ─── */
            aside.fi-sidebar {
                background: linear-gradient(to bottom, #0732b2, #1a006b) !important;
                border-inline-end: none !important;
            }

            /* Sidebar header (logo area) */
            aside.fi-sidebar .fi-sidebar-header {
                border-bottom: 1px solid rgba(255,255,255,0.15) !important;
            }

            /*
             * Sidebar text white — only for NON-active items.
             * Filament's active class is "fi-active", NOT "fi-sidebar-item-active".
             */
            aside.fi-sidebar .fi-sidebar-header,
            aside.fi-sidebar .fi-sidebar-header *,
            aside.fi-sidebar .fi-sidebar-group-label,
            aside.fi-sidebar .fi-sidebar-item:not(.fi-active) a,
            aside.fi-sidebar .fi-sidebar-item:not(.fi-active) a *,
            aside.fi-sidebar .fi-sidebar-item:not(.fi-active) button,
            aside.fi-sidebar .fi-sidebar-item:not(.fi-active) button * {
                color: rgba(255,255,255,0.85) !important;
            }
            aside.fi-sidebar .fi-sidebar-item:not(.fi-active) svg {
                color: rgba(255,255,255,0.7) !important;
                stroke: rgba(255,255,255,0.7) !important;
            }

            /* Hover on non-active items */
            aside.fi-sidebar .fi-sidebar-item:not(.fi-active) a:hover,
            aside.fi-sidebar .fi-sidebar-item:not(.fi-active) button:hover {
                background-color: rgba(255,255,255,0.12) !important;
                border-radius: 0.5rem !important;
            }
            aside.fi-sidebar .fi-sidebar-item:not(.fi-active) a:hover,
            aside.fi-sidebar .fi-sidebar-item:not(.fi-active) a:hover *,
            aside.fi-sidebar .fi-sidebar-item:not(.fi-active) button:hover,
            aside.fi-sidebar .fi-sidebar-item:not(.fi-active) button:hover * {
                color: #ffffff !important;
            }
            aside.fi-sidebar .fi-sidebar-item:not(.fi-active) a:hover svg,
            aside.fi-sidebar .fi-sidebar-item:not(.fi-active) button:hover svg {
                color: #ffffff !important;
                stroke: #ffffff !important;
            }

            /* Active sidebar item — white pill, blue text/icons */
            aside.fi-sidebar .fi-sidebar-item.fi-active > a,
            aside.fi-sidebar .fi-sidebar-item.fi-active > button {
                background: rgba(255,255,255,0.94) !important;
                border-radius: 0.5rem !important;
            }
            aside.fi-sidebar .fi-sidebar-item.fi-active > a,
            aside.fi-sidebar .fi-sidebar-item.fi-active > a *,
            aside.fi-sidebar .fi-sidebar-item.fi-active > button,
            aside.fi-sidebar .fi-sidebar-item.fi-active > button * {
                color: #0732b2 !important;
            }
            aside.fi-sidebar .fi-sidebar-item.fi-active > a svg,
            aside.fi-sidebar .fi-sidebar-item.fi-active > button svg {
                color: #0732b2 !important;
                stroke: #0732b2 !important;
                fill: none !important;
            }

            /* Sidebar collapse/close buttons */
            aside.fi-sidebar button[class*="collapse"],
            aside.fi-sidebar button[class*="close"] {
                color: rgba(255,255,255,0.7) !important;
            }

            /* ─── Topbar (header bar only) ─── */
            .fi-topbar {
                background: linear-gradient(to left, #0732b2, #1a006b) !important;
                border-bottom: none !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            }
            .fi-topbar > nav,
            .fi-topbar > nav > * {
                background-color: transparent !important;
            }
            /* White text only for items directly inside the topbar nav */
            .fi-topbar > nav a,
            .fi-topbar > nav button,
            .fi-topbar > nav span,
            .fi-topbar > nav svg,
            .fi-topbar > nav > * a,
            .fi-topbar > nav > * button,
            .fi-topbar > nav > * span,
            .fi-topbar > nav > * svg,
            .fi-topbar > nav > * > * a,
            .fi-topbar > nav > * > * button,
            .fi-topbar > nav > * > * span,
            .fi-topbar > nav > * > * svg {
                color: rgba(255,255,255,0.9) !important;
            }
            .fi-topbar > nav svg,
            .fi-topbar > nav > * svg,
            .fi-topbar > nav > * > * svg {
                stroke: rgba(255,255,255,0.9) !important;
            }

            /* ─── User menu dropdown (restore normal colors) ─── */
            .fi-dropdown-panel,
            .fi-dropdown-panel *,
            .fi-modal,
            .fi-modal * {
                color: inherit !important;
                stroke: inherit !important;
            }
            .fi-dropdown-panel {
                background: #ffffff !important;
                border: 1px solid #e5e7eb !important;
                border-radius: 0.75rem !important;
                box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
            }
            .fi-dropdown-panel a,
            .fi-dropdown-panel button {
                color: #374151 !important;
            }
            .fi-dropdown-panel svg {
                color: #6b7280 !important;
                stroke: #6b7280 !important;
            }
            .fi-dropdown-panel a:hover,
            .fi-dropdown-panel button:hover {
                background-color: #f3f4f6 !important;
                color: #0732b2 !important;
            }
            .fi-dropdown-panel a:hover svg,
            .fi-dropdown-panel button:hover svg {
                color: #0732b2 !important;
                stroke: #0732b2 !important;
            }
            /* Dropdown header (user name/email) */
            .fi-dropdown-header {
                border-bottom: 1px solid #e5e7eb !important;
            }
            .fi-dropdown-header p {
                color: #111827 !important;
            }
            .fi-dropdown-header p + p {
                color: #6b7280 !important;
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
            /* Keep login form text dark */
            .fi-simple-main-ctn,
            .fi-simple-main-ctn * {
                color: inherit;
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
            .fi-section {
                border-color: rgba(129,22,25,0.12) !important;
            }

            .fi-wi-stats-overview-stat {
                border-top: 3px solid #811619 !important;
                border-radius: 0.5rem !important;
            }

            /* Primary buttons */
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
