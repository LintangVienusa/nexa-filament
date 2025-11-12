<?php

namespace App\Filament\Resources\BastProjectResource\Pages;

use App\Filament\Resources\BastProjectResource;
use App\Models\BastProject;
use App\Models\HomeConnect;
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

class listHomeconnectDetails extends ListRecords
{
    use InteractsWithTable;
    protected static string $resource = BastProjectResource::class;

    protected static ?string $title = 'List Home Connect Details';

    protected static ?string $navigationLabel = 'Home Connect Details';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $slug = 'list-homeconnect-details';
     public ?int $bastId = null;
     public function mount(?string $bastId = null): void
    {
        $this->bastId = $bastId;
    }

    protected function getTableQuery(): Builder
    {
        return HomeConnect::query()
                ->Join('BastProject', 'HomeConnect.bast_id', '=', 'BastProject.bast_id')
                ->when($this->bastId, fn($query) => 
                        $query->where('HomeConnect.bast_id', $this->bastId)
                    )
                    ->select('HomeConnect.*', 'BastProject.site');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('bast_id')->label('BAST ID')->searchable(),
            TextColumn::make('site')->label('Site')->searchable(),
            TextColumn::make('sn_ont')->searchable(),
            TextColumn::make('notes')->searchable(),
            ImageColumn::make('foto_label_odp')
                ->label('Label ODP')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_label_odp ? asset('storage/'.$record->foto_label_odp) : null)
                ->width(150)
                ->height(150),
            ImageColumn::make('foto_hasil_ukur_odp')
                ->label('Hasil Ukur ODP')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_hasil_ukur_odp ? asset('storage/'.$record->foto_hasil_ukur_odp) : null)
                ->width(150)
                ->height(150),
            ImageColumn::make('foto_penarikan_outdoor')
                ->label('Penarikan Outdoor')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_penarikan_outdoor ? asset('storage/'.$record->foto_penarikan_outdoor) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('foto_aksesoris_ikr')
                ->label('Aksesoris IKR')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_aksesoris_ikr ? asset('storage/'.$record->foto_aksesoris_ikr) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('foto_sn_ont')
                ->label('SN ONT')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_sn_ont ? asset('storage/'.$record->foto_sn_ont) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('foto_depan_rumah')
                ->label('Depan Rumah')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_depan_rumah ? asset('storage/'.$record->foto_depan_rumah) : null)
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
