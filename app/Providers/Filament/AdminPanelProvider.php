<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->brandName(new HtmlString('
                <span class="hadirra-brand">
                    <span class="hadirra-brand-mark" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18.9 7a8 8 0 0 1 1.1 5v1a6 6 0 0 0 .8 3" />
                            <path d="M8 11a4 4 0 0 1 8 0v1a10 10 0 0 0 2 6" />
                            <path d="M12 11v2a14 14 0 0 0 2.5 8" />
                            <path d="M8 15a18 18 0 0 0 1.8 6" />
                            <path d="M4.9 19a22 22 0 0 1 -.9 -7v-1a8 8 0 0 1 12 -6.95" />
                        </svg>
                    </span>
                    <span class="hadirra-brand-copy">
                        <strong>HADIRRA</strong>
                        <em>Smart Attendance</em>
                    </span>
                </span>
            '))
            ->favicon(asset('favicon.svg'))
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->breadcrumbs(false)
            ->renderHook(\Filament\View\PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn (): \Illuminate\Contracts\View\View => view('filament.components.topbar-breadcrumb'))
            ->renderHook(\Filament\View\PanelsRenderHook::USER_MENU_BEFORE, fn (): \Illuminate\Contracts\View\View => view('filament.components.company-name', [
                'companyName' => \Illuminate\Support\Facades\DB::table('companies')->where('id', 1)->value('name'),
            ]))
            ->renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_FOOTER, fn (): \Illuminate\Contracts\View\View => view('filament.components.sidebar-logout'))
            ->colors([
                'primary' => Color::hex('#2563EB'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Absen'),
                NavigationGroup::make()->label('Pengaturan'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
