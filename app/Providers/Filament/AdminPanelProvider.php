<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\TotalCompanies;
use App\Filament\Admin\Widgets\UsagePieChart;
use App\Filament\Widgets\ManagerUsageList;
use App\Models\User;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('100px')
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('web')
            ->passwordReset()
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('4rem')
            ->favicon(asset('images/favicon.png'))
            ->renderHook('panels::body.start', fn () =>'
                <style>
                    /* =======================
                    SIDEBAR – LIGHT MODE
                    ======================= */
                    .fi-sidebar {
                        background-color: #E5E7EB !important;
                        box-shadow: inset -1px 0 0 #D1D5DB;
                    }

                    /* =======================
                    SIDEBAR – DARK MODE
                    ======================= */
                    .dark .fi-sidebar {
                        background-color: #0F172A !important; /* slate-900 */
                        box-shadow: inset -1px 0 0 #1F2937;   /* slate-800 */
                    }
                    /* =======================
                    BASE CARD (LIGHT MODE)
                    ======================= */
                    .app-card {
                        background-color: #F9FAFB;
                        border: 1px solid #E5E7EB;
                        border-radius: 14px;
                        transition: all 0.2s ease;
                    }

                    .app-card:hover {
                        background-color: #FFFFFF;
                        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
                        transform: translateY(-2px);
                    }

                    .app-card-title {
                        color: #111827;
                        font-weight: 600;
                    }

                    .app-card-muted {
                        color: #6B7280;
                        font-size: 0.875rem;
                    }

                    /* =======================
                    DARK MODE OVERRIDES
                    ======================= */
                    .dark .app-card {
                        background-color: #111827; /* slate-900 */
                        border: 1px solid #1F2937; /* slate-800 */
                    }

                    .dark .app-card:hover {
                        background-color: #1F2937;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.4);
                    }

                    .dark .app-card-title {
                        color: #F9FAFB;
                    }

                    .dark .app-card-muted {
                        color: #9CA3AF;
                    }
                </style>')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Admin\Pages\UserPermissions::class,
                //\App\Filament\Pages\UserList::class,
            ])
            ->resources([
                // Removed CompanyResource
                // Make sure UserResource actually exists, otherwise comment it out too
                // \App\Filament\Admin\Resources\UserResource::class,
            ])
            //->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                TotalCompanies::class,
                UsagePieChart::class,
                ManagerUsageList::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}