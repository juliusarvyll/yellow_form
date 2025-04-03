<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Dean\YellowFormResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Resources\StudentResource;

class DeanPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dean')
            ->path('dean')
            ->login()
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->brandName('Dean Portal')
            ->resources([
                YellowFormResource::class,
                StudentResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages/Dean'), for: 'App\\Filament\\Pages\\Dean')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets/Dean'), for: 'App\\Filament\\Widgets\\Dean')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\DeanDashboardWidget::class,
                \App\Filament\Widgets\YellowFormStatsOverview::class,
            ])
            ->authGuard('web')
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
                \App\Http\Middleware\EnsureDeanHasDepartment::class,
                \App\Http\Middleware\RedirectBasedOnRole::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
