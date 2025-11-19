<?php

namespace App\Filament\Resources\BastProjectResource\Pages;

use App\Filament\Resources\BastProjectResource;
use App\Models\BastProject;
use App\Models\ODCDetail;
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

class ListOdcDetails extends ListRecords
{
    use InteractsWithTable;
    protected static string $resource = BastProjectResource::class;
    protected static ?string $title = 'List ODC Details';

    protected static ?string $navigationLabel = 'ODC Details';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $slug = 'list-odc-details';
     public ?int $bastId = null;
     public function mount(?string $bastId = null): void
    {
        $this->bastId = $bastId;
    }

    protected function getTableQuery(): Builder
    {
        return ODCDetail::query()
                ->Join('BastProject', 'ODCDetail.bast_id', '=', 'BastProject.bast_id')
                ->when($this->bastId, fn($query) => 
                        $query->where('ODCDetail.bast_id', $this->bastId)
                    )
                    ->select('ODCDetail.*', 'BastProject.site');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('bast_id')->label('BAST ID')->searchable(),
            TextColumn::make('site')->label('Site')->searchable(),
            TextColumn::make('odc_name')->searchable(),
            TextColumn::make('notes')->searchable(),
            ImageColumn::make('instalasi')
                ->label('Instalasi ODC')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->instalasi ? asset('storage/'.$record->instalasi) : null)
                ->width(150)
                ->height(150),
            ImageColumn::make('odc_terbuka')
                ->label('ODC Terbuka')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->odc_terbuka ? asset('storage/'.$record->odc_terbuka) : null)
                ->width(150)
                ->height(150),
            ImageColumn::make('odc_tertutup')
                ->label('ODC Tertutup')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->odc_tertutup ? asset('storage/'.$record->odc_tertutup) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('power_optic_olt')
                ->label('Power Optic dari OLT')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->hasil_ukur_opm ? asset('storage/'.$record->hasil_ukur_opm) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('flexing_conduit')
                ->label('Flexing Conduit')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->labeling_odc ? asset('storage/'.$record->labeling_odc) : null)
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
