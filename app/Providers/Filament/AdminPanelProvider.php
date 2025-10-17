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
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\View\PanelsRenderHook;
use App\Filament\Resources\ProfileResource;
use Filament\Navigation\UserMenuItem;
use Filament\Pages\Auth\EditProfile;
use Illuminate\Support\Facades\Auth;


class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('HRMS - DPNG')
            ->profile()
            ->font('Roboto')
            ->userMenuItems([
                'profile' => UserMenuItem::make()
                    ->label('Profil Saya')
                    ->icon('heroicon-o-user')
                    ->url(function () {
                        $recordId = auth()->user()->email;
                        return ProfileResource::getUrl('edit', ['record' => $recordId]);
                    }),
            ])

            ->renderHook(PanelsRenderHook::HEAD_END, function () {
                $favicon = asset('assets/images/LOGO PT DAPOER POESAT NUSANTARA-07.png'); // path ke favicon
                return <<<HTML
                <title>HRMS - DPN</title>
                    <link rel="icon" type="image/png" href="{$favicon}" />
                    <link rel="shortcut icon" href="{$favicon}" />
                HTML;
            })
            ->login()
            ->colors([
                'primary' => '#cccccc',
            ])
            ->brandLogo(asset('assets/images/LOGO PT DAPOER POESAT NUSANTARA-03.png'))
            ->brandLogoHeight('10rem')
            ->renderHook(PanelsRenderHook::HEAD_END, function () {
                $logoLight = asset('assets/images/LOGO PT DAPOER POESAT NUSANTARA-03.png');
                $logoDark = asset('assets/images/LOGO PT DAPOER POESAT NUSANTARA-05.png');

                return <<<HTML
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const logo = document.querySelector('.filament-brand a img');
                        if (!logo) return;

                        function updateLogo() {
                            logo.src = document.documentElement.classList.contains('dark')
                                ? '{$logoDark}'
                                : '{$logoLight}';
                        }

                        updateLogo();

                        const observer = new MutationObserver(updateLogo);
                        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                    });
                </script>
                HTML;
            })
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->renderHook(PanelsRenderHook::BODY_END, function () {
                $logo = asset('assets/images/LOGO PT DAPOER POESAT NUSANTARA-07.png');
                $logoWhite = asset('assets/images/LOGO PT DAPOER POESAT NUSANTARA-07.png');

                return <<<HTML
                <div id="watermark-container"
                    class="fixed inset-0 pointer-events-none -z-10"
                    style="transform: rotate(65deg); transform-origin: center center;">
                </div>

                <script>
                    (function() {
                        const container = document.getElementById('watermark-container');
                        const logo = "{$logo}";
                        const logoWhite = "{$logoWhite}";
                        function createWatermark() {
                            container.innerHTML = '';
                            const isDark = document.documentElement.classList.contains('dark');
                            const imgSrc = isDark ? logoWhite : logo;
                            const screenWidth = window.innerWidth * 2.5;
                            const screenHeight = window.innerHeight * 2.5;
                            const cols = 8; // jumlah kolom
                            const rows = 9; // jumlah baris

                            const baseWidthRem = 35;
                            const rootFontSize = parseFloat(getComputedStyle(document.documentElement).fontSize);
                            const imgWidthPx = baseWidthRem * rootFontSize;

                            const padding = 1; // jarak tepi layar
                            const gap = (Math.min(screenWidth, screenHeight) -  imgWidthPx) / (Math.max(cols, rows) - 1);

                            const verticalOffset = -screenHeight * 0.3;

                            for (let i = 0; i < cols; i++) {
                                for (let j = 0; j < rows; j++) {
                                    const img = document.createElement('img');
                                    img.src = imgSrc;

                                    const posX = padding + i * gap;
                                    const posY = padding + j * gap + verticalOffset;

                                    img.style.position = 'fixed';
                                    img.style.left = posX + 'px';
                                    img.style.top = posY + 'px';
                                    img.style.width = imgWidthPx + 'px';
                                    img.style.height = 'auto';
                                    img.style.opacity = 0.03;
                                    img.style.pointerEvents = 'none';
                                    img.style.transform = 'rotate(-65deg)';

                                    container.appendChild(img);
                                }
                            }
                        }

                        createWatermark();

                        window.addEventListener('resize', createWatermark);

                        // update watermark jika dark mode berubah
                        const observer = new MutationObserver(createWatermark);
                        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                    })();
                </script>
                HTML;
            })
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
            ->homeUrl('/admin');
    }
}
