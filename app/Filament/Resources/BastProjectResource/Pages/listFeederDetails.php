<?php

namespace App\Filament\Resources\BastProjectResource\Pages;

use App\Filament\Resources\BastProjectResource;
use App\Models\BastProject;
use App\Models\FeederDetail;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\Action;
use App\Exports\BastPoleExport;
use Filament\Tables\Columns\ImageColumn;

class listFeederDetails extends ListRecords
{
    use InteractsWithTable;
    protected static string $resource = BastProjectResource::class;

    protected static ?string $title = 'List Feeder Details';

    protected static ?string $navigationLabel = 'Feeder Details';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $slug = 'list-feeder-details';
    public ?int $bastId = null;
    public function mount(?string $bastId = null): void
    {
        $this->bastId = $bastId;
    }

    protected function getTableQuery(): Builder
    {
        return FeederDetail::query()
                ->Join('BastProject', 'FeederDetail.bast_id', '=', 'BastProject.bast_id')
                ->when($this->bastId, fn($query) => 
                        $query->where('FeederDetail.bast_id', $this->bastId)
                    )
                    ->select('FeederDetail.*', 'BastProject.site');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('bast_id')->label('BAST ID')->searchable(),
            TextColumn::make('site')->label('Site')->searchable(),
            TextColumn::make('feeder_name')->searchable(),
            TextColumn::make('notes')->searchable(),
            ImageColumn::make('foto_utara')
                ->label('Utara')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_utara ? asset('storage/'.$record->foto_utara) : null)
                ->width(150)
                ->height(150),
            ImageColumn::make('foto_barat')
                ->label('Barat')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_barat ? asset('storage/'.$record->foto_barat) : null)
                ->width(150)
                ->height(150),
            ImageColumn::make('foto_selatan')
                ->label('Selatan')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_selatan ? asset('storage/'.$record->foto_selatan) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('foto_timur')
                ->label('Timur')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_timur ? asset('storage/'.$record->foto_timur) : null)
                ->width(150)
                ->height(150),

            TextColumn::make('progress_percentage')
                ->label('Progress (%)')
                ->formatStateUsing(fn ($state) => '
                    <div style="width:300%; background:#e5e7eb; border-radius:8px; overflow:hidden;">
                        <div style="width:'.$state.'%; background:'.
                            ($state < 30 ? '#ef4444' : ($state < 70 ? '#f59e0b' : '#10b981')).
                            '; height:8px;"></div>
                    </div>
                    <div style="font-size:12px; text-align:center; margin-top:2px;">'.number_format($state,0).'%</div>
                ')
                ->html() 
                ->sortable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            // Tables\Actions\ViewAction::make(),
            // Action::make('export_implementation')
            //         ->label('Tiang')
            //         ->icon('heroicon-o-document-arrow-down')
            //         ->action(fn ($record) => Excel::download(new BastPoleExport($record), "Implementation_{$record->kode}.xlsx")),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            // Tables\Actions\DeleteBulkAction::make(),
        ];
    }


}
