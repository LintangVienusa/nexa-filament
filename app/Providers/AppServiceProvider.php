<?php

namespace App\Providers;

use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use App\Http\Responses\LoginResponse as CustomLoginResponse;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Navigation\UserMenuItem;
use Filament\Facades\Filament;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponse::class, CustomLoginResponse::class);
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        
        
        Filament::serving(function () {
            

            FilamentView::registerRenderHook(

                PanelsRenderHook::USER_MENU_PROFILE_BEFORE,
                function () {
                    $user = auth()->user();

                    $employee = $user?->employee;
                    $organization = $employee?->organization;
                    $avatarUrl = $user?->getFilamentAvatarUrl() ?? asset('images/default-avatar.png');
                    $name =strtoupper(optional($user?->employee)->first_name ?? '-');
                    $position = e(optional($user?->employee)->job_title ?? optional($user?->employee)->job_title ?? '-');
                    $division = $organization?->divisi_name ?? '-';
                    $unit = $organization?->unit_name ?? '-';

                    return <<<HTML
                        <div class="fi-dropdown-list p-3 w-20rem">
                            <div class="flex items-center gap-3">
                                <img
                                    src="{$avatarUrl}"
                                    alt="Avatar"
                                    
                                    style="width: 6rem; height: 7rem;"
                                    class=" object-cover border border-gray-300"
                                    onerror="this.src='" . asset('images/default-avatar.png') . "'"
                                >
                                <div class="flex flex-col justify-center">
                                    <span class="font-semibold text-sm leading-tight text-gray-900 dark:text-white uppercase">{$name}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{$position}</span>
                                </div>
                            </div>
                            <div class="fi-dropdown-divider my-2"></div>
                        </div>
                    HTML;
                }
            );
        });
    }
}
