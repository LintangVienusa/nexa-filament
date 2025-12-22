<?php

namespace App\Filament\Resources\BastProjectResource\Pages;

use App\Filament\Resources\BastProjectResource;
use App\Models\BastProject;
use App\Models\HomeConnect;
use Filament\Forms\Components\Textarea;
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
use Filament\Forms\Form as FilamentForm;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Filament\Actions\Action as HeaderAction;
use Livewire\Attributes\On;

class ListHomeConnectDetails extends ListRecords
{
    use InteractsWithTable;

    protected static string $resource = BastProjectResource::class;

    protected static ?string $title = 'List Home Connect Details';

    protected static ?string $navigationLabel = 'Home Connect Details';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    public ?string $bast_id = null;

    public function mount(?string $bast_id = null): void
    {
        $this->bast_id = $bast_id;
    }

    protected function getTableQuery(): Builder
    {
        return HomeConnect::query()
            ->Join('BastProject', 'HomeConnect.bast_id', '=', 'BastProject.bast_id')
            ->where('HomeConnect.bast_id', $this->bast_id)
            ->select('HomeConnect.*', 'BastProject.bast_id')->orderByDesc('updated_at');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('site')->label('Site')->searchable(['BastProject.site']),
            TextColumn::make('odp_name')->label('ODP')->searchable(['HomeConnect.odp_name']),
            TextColumn::make('port_odp')->label('Port ODP')->searchable(['HomeConnect.port_odp']),
            TextColumn::make('status_port')->label('Status')->searchable(['HomeConnect.status_port']),
            TextColumn::make('merk_ont')->label('Merk ONT')->searchable(['HomeConnect.merk_ont']),
            TextColumn::make('sn_ont')->label('SN ONT')->searchable(['HomeConnect.sn_ont']),
            TextColumn::make('notes')->searchable(['HomeConnect.notes']),

            ImageColumn::make('foto_label_id_plg')
                ->label('ID Pelanggan di ODP')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_label_id_plg ? asset('storage/' . $record->foto_label_id_plg) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('foto_qr')
                ->label('QR Code')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_qr ? asset('storage/' . $record->foto_qr) : null)
                ->width(150)
                ->height(150),
            ImageColumn::make('foto_label_odp')
                ->label('ODP')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_label_odp ? asset('storage/' . $record->foto_label_odp) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('foto_sn_ont')
                ->label('SN ONT')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->foto_sn_ont ? asset('storage/' . $record->foto_sn_ont) : null)
                ->width(150)
                ->height(150),
            TextColumn::make('progress_percentage')
                ->label('Progress (%)')
                ->formatStateUsing(fn($state) => '
                    <div style="width:300%; background:#e5e7eb; border-radius:8px; overflow:hidden;">
                        <div style="width:' . $state . '%; background:' .
                    ($state < 30 ? '#ef4444' : ($state < 70 ? '#f59e0b' : '#10b981')) .
                    '; height:8px;"></div>
                    </div>
                    <div style="font-size:12px; text-align:center; margin-top:2px;">' . number_format($state, 0) . '%</div>
                ')
                ->html()
                ->sortable(),
            TextColumn::make('status')
                ->badge()
                ->formatStateUsing(fn($state) => match ($state) {
                    'submit' => 'submit',
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    default => $state,
                })
                ->color(fn($state): string => match ($state) {
                    'submit' => 'warning',
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'primary',
                })
                ->sortable(),
            
        ];

    }

    #[On('rotate-photo')]
    protected function rotateImage(?string $path): void
    {
        if (!$path) return;

        $fullPath = Storage::disk('public')->path($path);

        if (!file_exists($fullPath)) return;

        $manager = new ImageManager(new Driver());

        $image = $manager->read($fullPath);
        $image->rotate(-90);
        $image->save($fullPath);
    }


    protected function getTableActions(): array
    {
        return [
            Action::make('view')
                ->label('View')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->modalHeading('Detail Home Connect')
                ->modalWidth('3xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(fn ($record) =>
                    view('filament.homeconnect.view-detail', [
                        'record' => $record,
                    ])
                ),
            //  Action::make('rotate_foto_label_id_plg')
            //     ->label('Rotate ID Pelanggan')
            //     ->icon('heroicon-o-arrow-path')
            //     ->requiresConfirmation()
            //     ->action(function ($record) {
            //         $this->rotateImage($record->foto_label_id_plg);
            //     })
            //     ->after(function () {
            //         Notification::make()
            //             ->title('Foto berhasil di-rotate')
            //             ->success()
            //             ->send();
            //         $this->dispatch('refresh');
            //     })
            //     ->visible(fn ($record) => filled($record->foto_label_id_plg)),

            // Action::make('rotate_foto_qr')
            //     ->label('Rotate QR')
            //     ->icon('heroicon-o-arrow-path')
            //     ->requiresConfirmation()
            //     ->action(function ($record) {
            //         $this->rotateImage($record->foto_qr);
            //     })
            //     ->after(function () {
            //         Notification::make()
            //             ->title('Foto berhasil di-rotate')
            //             ->success()
            //             ->send();

            //         $this->dispatch('refresh');
            //     })
            //     ->visible(fn ($record) => filled($record->foto_qr)),

            // Action::make('rotate_foto_label_odp')
            //     ->label('Rotate ODP')
            //     ->icon('heroicon-o-arrow-path')
            //     ->requiresConfirmation()
            //     ->action(function ($record) {
            //         $this->rotateImage($record->foto_label_odp);
            //     })
            //     ->after(function () {
            //         Notification::make()
            //             ->title('Foto berhasil di-rotate')
            //             ->success()
            //             ->send();

            //         $this->dispatch('refresh');
            //     })
            //     ->visible(fn ($record) => filled($record->foto_label_odp)),

            // Action::make('rotate_foto_sn_ont')
            //     ->label('Rotate SN ONT')
            //     ->icon('heroicon-o-arrow-path')
            //     ->requiresConfirmation()
            //     ->action(function ($record) {
            //         $this->rotateImage($record->foto_sn_ont);
            //     })->after(function () {
            //         Notification::make()
            //             ->title('Foto berhasil di-rotate')
            //             ->success()
            //             ->send();

            //         $this->dispatch('refresh');
            //     })
            //     ->visible(fn ($record) => filled($record->foto_sn_ont)),
            Action::make('maps')
                ->label('Lihat Maps')
                ->icon('heroicon-o-map')
                ->url(fn($record) => "https://www.google.com/maps?q={$record->latitude},{$record->longitude}")
                ->openUrlInNewTab()
                ->color('success'),
            Action::make('approve')
                ->label('Approved')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn($record) => $record->status === 'submit' && (int)$record->progress_percentage >= 100)
                ->action(function ($record) {
                    HomeConnect::where('bast_id', $record->bast_id)->where('id_pelanggan', $record->id_pelanggan)
                        ->update([
                            'status' => 'approved',
                            'approval_by' => Auth::user()->email,
                            'approval_at' => now(),
                        ]);
                })->after(fn() => $this->dispatch('refresh'))
                ->successNotificationTitle('Data berhasil di-approve'),
            Action::make('reject')
                ->label('Rejected')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn($record) => $record->status === 'submit' && (int)$record->progress_percentage >= 100)
                ->form([
                    Textarea::make('notes')
                        ->label('Alasan Reject / Catatan')
                        ->rows(4)
                        ->required(),
                ])
                ->mountUsing(function (FilamentForm $form, $record) {
                    $detail = HomeConnect::where('bast_id', $record->bast_id)
                        ->where('id_pelanggan', $record->id_pelanggan)
                        ->first();

                    $form->fill([
                        'notes' => '',
                        'old_notes' => $detail?->notes,
                    ]);
                })
                ->action(function ($record, array $data) {
                    $detail = HomeConnect::where('bast_id', $record->bast_id)
                        ->where('id_pelanggan', $record->id_pelanggan)
                        ->first();

                    $oldNotes = $detail?->notes ?? '';
                    $newEntry = "[" . now() . "] " . Auth::user()->email . "-> Reject :\n" .
                        $data['notes'] . "\n\n";
                    $finalNotes = $newEntry . " | " . $oldNotes;

                    HomeConnect::where('bast_id', $record->bast_id)
                        ->where('id_pelanggan', $record->id_pelanggan)
                        ->update([
                            'status' => 'rejected',
                            'approval_by' => Auth::user()->email,
                            'approval_at' => now(),
                            'notes' => $finalNotes,
                        ]);
                })
                ->after(fn() => $this->dispatch('refresh'))
                ->successNotificationTitle('Data berhasil di-reject'),
            Action::make('export_ba_pdf')
                ->label('Export BA PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->action(function ($record) {
                    $pdf = app(DownloadBAService::class)->downloadBA($record);
                    return response()->streamDownload(
                        fn() => print($pdf->output()),
                        "BA_Pelanggan_{$record->id_pelanggan}.pdf",
                        [
                            "Content-Type" => "application/pdf",
                        ]
                    );
                })->visible(fn($record) => $record->status === 'approved'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('refresh_page')
                ->label('Refresh Page')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                // ->url(url()->previous()),
                ->extraAttributes([
                    'onclick' => 'window.location.reload();',
                ]),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('export_ba_pdf_bulk')
                ->label('Export BA PDF Bulk')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->action(function (\Illuminate\Support\Collection $records) {

                    $records = $records->filter(fn($record) => $record->status === 'approved');

                    if ($records->isEmpty()) {
                        Notification::make()
                            ->title('Tidak ada record yang approved!')
                            ->danger()
                            ->send();
                        return;
                    }

                    $zipFileName = 'BA_Pelanggan_' . now()->format('Ymd_His') . '.zip';
                    $zipPath = storage_path($zipFileName);

                    $zip = new \ZipArchive();
                    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {

                        foreach ($records as $record) {
                            $pdf = app(DownloadBAService::class)->downloadBA($record);
                            $pdfFileName = "BA_Pelanggan_{$record->id_pelanggan}.pdf";
                            $zip->addFromString($pdfFileName, $pdf->output());
                        }

                        $zip->close();
                    }

                    return response()->download($zipPath)->deleteFileAfterSend(true);
                }),
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


    
}
