<?php

namespace App\Filament\Resources\BastProjectResource\Pages;

use App\Filament\Resources\BastProjectResource;
use App\Models\BastProject;
use App\Models\ODPDetail;
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

class listOdpDetails extends ListRecords
{
    use InteractsWithTable;
    protected static string $resource = BastProjectResource::class;

    protected static ?string $title = 'List ODP Details';

    protected static ?string $navigationLabel = 'ODP Details';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $slug = 'list-odp-details';
    public ?int $bastId = null;

    public function mount(?string $bastId = null): void
    {
        $this->bastId = $bastId;
    }

    protected function getTableQuery(): Builder
    {
        return ODPDetail::query()
                ->Join('BastProject', 'ODPDetail.bast_id', '=', 'BastProject.bast_id')
                ->when($this->bastId, fn($query) => 
                        $query->where('ODPDetail.bast_id', $this->bastId)
                    )
                    ->select('ODPDetail.*', 'BastProject.site');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('bast_id')->label('BAST ID')->searchable(),
            TextColumn::make('site')->label('Site')->searchable(),
            TextColumn::make('odc_name')->searchable(),
            TextColumn::make('odp_name')->searchable(),
            TextColumn::make('notes')->searchable(),
            ImageColumn::make('instalasi')
                ->label('Instalasi')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->instalasi ? asset('storage/'.$record->instalasi) : null)
                ->width(150)
                ->height(150),
            ImageColumn::make('odp_terbuka')
                ->label('ODP Terbuka')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->odp_terbuka ? asset('storage/'.$record->odp_terbuka) : null)
                ->width(150)
                ->height(150),
            ImageColumn::make('odp_tertutup')
                ->label('ODP Tertutup')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->odp_tertutup ? asset('storage/'.$record->odp_tertutup) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('hasil_ukur_opm')
                ->label('Hasil Ukur OPM')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->hasil_ukur_opm ? asset('storage/'.$record->hasil_ukur_opm) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('labeling_odp')
                ->label('Labeling ODP')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->labeling_odp ? asset('storage/'.$record->labeling_odp) : null)
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
