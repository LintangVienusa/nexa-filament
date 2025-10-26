<?php

// namespace App\Filament\Resources\AttendanceResource\Widgets;

// use Filament\Widgets\Widget;

// use Shkubu18\FilamentWidgetTabs\Concerns\HasWidgetTabs;
// use Shkubu18\FilamentWidgetTabs\Components\WidgetTab as FixedWidgetTab;


// class AttendanceTabs extends Widget
// {
//      use HasWidgetTabs;
//     // protected static string $view = 'filament.resources.attendance-resource.widgets.attendance-tabs';

 
//     public function getWidgetTabs(): array
//     {
//         return [
//             'all' => FixedWidgetTab::make()
//                 ->label('All Posts')
//                 ->icon('heroicon-o-chat-bubble-left-right')
//                 ->value(Post::count()),
//             'published' => FixedWidgetTab::make()
//                 ->label('Published')
//                 ->icon('heroicon-o-eye')
//                 ->value(Post::where('status', PostStatusEnum::PUBLISHED)->count())
//                 ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PostStatusEnum::PUBLISHED)),
//             'drafts' => FixedWidgetTab::make()
//                 ->label('Drafts')
//                 ->icon('heroicon-o-archive-box')
//                 ->value(Post::where('status', PostStatusEnum::DRAFT)->count())
//                 ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PostStatusEnum::DRAFT)),
//         ];
//     }

    
// }
