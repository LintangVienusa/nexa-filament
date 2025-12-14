<?php

namespace App\Filament\Resources\HomeConnectResource\Widgets;

use App\Models\HomeConnect;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HomeConnectStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            //
            Stat::make('Total Pekerjaan', HomeConnect::count())
                ->description('Total data HomeConnect')
                ->icon('heroicon-o-briefcase'),
            
            Stat::make('Port Idle', HomeConnect::where('status_port', 'idle')->count())
                ->description('Status idle')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Port Used', HomeConnect::where('status_port', 'used')->count())
                ->description('Status used')
                ->icon('heroicon-o-x-circle')
                ->color('warning'),
        ];
    }
}
