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
use Filament\Tables\Filters\SelectFilter;
use App\Services\DownloadBAService;
use Illuminate\Support\Facades\Auth;


class listHomeconnectDetails extends ListRecords
{
    use InteractsWithTable;
    protected static string $resource = BastProjectResource::class;

    protected static ?string $title = 'List Home Connect Details';

    protected static ?string $navigationLabel = 'Home Connect Details';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $slug = 'list-homeconnect-details';
     public ?string  $site = null;
     
     public function mount(?string $site = null): void
    {
        $this->site = $site;
    }

    protected function getTableQuery(): Builder
    {
        return HomeConnect::query()
                ->Join('BastProject', 'HomeConnect.bast_id', '=', 'BastProject.bast_id')
                ->where('HomeConnect.site', $this->site)
                    ->select('HomeConnect.*', 'BastProject.site');
    }

    protected function getTableColumns(): array
    {
        return [
            // TextColumn::make('bast_id')->label('BAST ID')->searchable(),
            TextColumn::make('site')->label('Site')->searchable(),
            TextColumn::make('odp_name')->label('ODP')->searchable(),
            TextColumn::make('port_odp')->label('Port ODP')->searchable(),
            TextColumn::make('status_port')->label('Status')->searchable(),
            TextColumn::make('merk_ont')->label('Merk ONT')->searchable(),
            TextColumn::make('sn_ont')->label('SN ONT')->searchable(),
            TextColumn::make('notes')->searchable(),

            ImageColumn::make('foto_label_id_plg')
                ->label('ID Pelanggan di ODP')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_label_id_plg ? asset('storage/'.$record->foto_label_id_plg) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('foto_qr')
                ->label('QR Code')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_qr ? asset('storage/'.$record->foto_qr) : null)
                ->width(150)
                ->height(150),
            ImageColumn::make('foto_label_odp')
                ->label('ODP')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_label_odp ? asset('storage/'.$record->foto_label_odp) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('foto_sn_ont')
                ->label('SN ONT')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_sn_ont ? asset('storage/'.$record->foto_sn_ont) : null)
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
            TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'submit' => 'submit',
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        default => $state,
                    })
                    ->color(fn ($state): string => match ($state) {
                        'submit' => 'warning',
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'primary',
                    })
                    ->sortable(),
        ];
        
    }

    protected function getTableActions(): array
    {
        return [
            // Action::make('maps')
            //             ->label('Lihat Maps')
            //             ->icon('heroicon-o-map')
            //             ->url(fn ($record) => "https://www.google.com/maps?q={$record->latitude},{$record->longitude}")
            //             ->openUrlInNewTab()
            //             ->color('success'),
            // Action::make('pending')
            //     ->label('Pending')
            //     ->icon('heroicon-o-check-circle')
            //     ->color('warning')
            //     ->requiresConfirmation()
            //     ->visible(fn ($record) => $record->status === 'submit'  && (int) $record->progress_percentage >= 100)
            //     ->action(function ($record) {
            //         HomeConnect::where('bast_id', $record->bast_id)->where('id_pelanggan', $record->id_pelanggan)
            //         ->update([
            //             'status'       => 'pending',
            //             'approval_by'  => Auth::user()->email,
            //             'approval_at'  => now(),
            //         ]);
            //     })->after(fn () => $this->dispatch('refresh'))
            //     ->successNotificationTitle('Data berhasil di-pending'),
            Action::make('approve')
                ->label('Approved')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === 'submit' && (int) $record->progress_percentage >= 100)
                ->action(function ($record) {
                    HomeConnect::where('bast_id', $record->bast_id)->where('id_pelanggan', $record->id_pelanggan)
                    ->update([
                        'status'       => 'approved',
                        'approval_by'  => Auth::user()->email,
                        'approval_at'  => now(),
                    ]);
                })->after(fn () => $this->dispatch('refresh'))
                ->successNotificationTitle('Data berhasil di-approve'),
            Action::make('reject')
                ->label('Rejected')
                ->icon('heroicon-o-check-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === 'submit'  && (int) $record->progress_percentage >= 100)
                ->action(function ($record) {
                    HomeConnect::where('bast_id', $record->bast_id)->where('id_pelanggan', $record->id_pelanggan)
                    ->update([
                        'status'       => 'rejected',
                        'approval_by'  => Auth::user()->email,
                        'approval_at'  => now(),
                    ]);
                })->after(fn () => $this->dispatch('refresh'))
                ->successNotificationTitle('Data berhasil di-reject'),
            Action::make('export_ba_pdf')
                ->label('Export BA PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->action(function ($record) {
                    $pdf = app(DownloadBAService::class)->downloadBA($record);

                    // return response()->streamDownload(
                    //     fn () => print($pdf->output()),
                    //     "BA_{$record->site}.pdf"
                    // );
                    return response()->streamDownload(
                    fn () =>print($pdf->output()),
                    "BA_{$record->site}.pdf",
                    [
                        "Content-Type" => "application/pdf",
                    ]
                );
                })->visible(fn ($record) => $record->status === 'approved'),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('odp_name')
                ->label('ODP Name')
                ->options(
                    HomeConnect::query()
                        ->when($this->site, fn($q) => $q->where('site', $this->site))
                        ->whereNotNull('odp_name')
                        ->distinct()
                        ->orderBy('odp_name')
                        ->pluck('odp_name', 'odp_name')
                        ->filter() // buang null/empty
                        ->mapWithKeys(fn($v) => [(string)$v => (string)$v]) // pastikan string keys
                        ->toArray()
                )
                ->searchable(),
            SelectFilter::make('status_port')
                ->label('Status Port')
                ->options(
                    HomeConnect::query()
                        ->when($this->site, fn($q) => $q->where('site', $this->site))
                        ->whereNotNull('status_port')
                        ->distinct()
                        ->orderBy('status_port')
                        ->pluck('status_port', 'status_port')
                        ->filter()
                        ->mapWithKeys(fn($v) => [(string)$v => (string)$v])
                        ->toArray()
                )
                ->searchable(),

            SelectFilter::make('merk_ont')
                ->label('Merk ONT')
                ->options(
                    HomeConnect::query()
                        ->when($this->site, fn($q) => $q->where('site', $this->site))
                        ->whereNotNull('merk_ont')
                        ->distinct()
                        ->orderBy('merk_ont')
                        ->pluck('merk_ont', 'merk_ont')
                        ->filter()
                        ->mapWithKeys(fn($v) => [(string)$v => (string)$v])
                        ->toArray()
                )
                ->searchable(),
            
        ];
    }

   

    protected function getTableBulkActions(): array
    {
        return [
            // Tables\Actions\DeleteBulkAction::make(),
        ];
    }
}
