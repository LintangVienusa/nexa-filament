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
use App\Exports\BastFeederExport;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Form as FilamentForm;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;

class ListFeederDetails extends ListRecords
{
    use InteractsWithTable;
    protected static string $resource = BastProjectResource::class;

    protected static ?string $title = 'List Feeder Details';

    protected static ?string $navigationLabel = 'Feeder Details';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $slug = 'list-feeder-details';
    public ?string $site = null;
    public function mount(?string $site = null): void
    {
        $this->site = $site;
    }

    protected function getTableQuery(): Builder
    {
        return FeederDetail::query()
                ->Join('BastProject', 'FeederDetail.bast_id', '=', 'BastProject.bast_id')
                ->when($this->site, fn($query) =>
                        $query->where('FeederDetail.site', $this->site)
                    )
                    ->select('FeederDetail.*', 'BastProject.site');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('bast_id')->label('BAST ID')->searchable(['BastProject.bast_id']),
            TextColumn::make('site')->label('Site')->searchable(['BastProject.site']),
            TextColumn::make('feeder_name')->searchable(['FeederDetail.feeder_name']),
            TextColumn::make('notes')->searchable(['FeederDetail.notes']),
            ImageColumn::make('pulling_cable')
                ->label('Pulling Cable A')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->pulling_cable ? asset('storage/'.$record->pulling_cable) : null)
                ->width(150)
                ->height(150),
            ImageColumn::make('pulling_cable_b')
                ->label('Pulling Cable B')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->pulling_cable_b ? asset('storage/'.$record->pulling_cable_b) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('instalasi')
                ->label('Instalasi Accesoris')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->instalasi ? asset('storage/'.$record->instalasi) : null)
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
            // Action::make('pending')
            //     ->label('Pending')
            //     ->icon('heroicon-o-check-circle')
            //     ->color('warning')
            //     ->requiresConfirmation()
            //     ->visible(fn ($record) => $record->status === 'submit'  && (int) $record->progress_percentage >= 100)
            //     ->action(function ($record) {
            //         FeederDetail::where('bast_id', $record->bast_id)->where('feeder_name', $record->feeder_name)
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
                    FeederDetail::where('bast_id', $record->bast_id)->where('feeder_name', $record->feeder_name)
                    ->update([
                        'status'       => 'approved',
                        'approval_by'  => Auth::user()->email,
                        'approval_at'  => now(),
                    ]);
                })->after(fn () => $this->dispatch('refresh'))
                ->successNotificationTitle('Data berhasil di-approve'),
            Action::make('reject')
                ->label('Rejected')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === 'submit' && (int) $record->progress_percentage >= 100)

                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Alasan Reject / Catatan')
                        ->rows(4)
                        ->required(),
                ])

                ->mountUsing(function (FilamentForm $form, $record) {
                    $detail = FeederDetail::where('bast_id', $record->bast_id)
                        ->where('feeder_name', $record->feeder_name)
                        ->first();


                    $form->fill([
                        'notes' => '',
                        'old_notes' => $detail?->notes,
                    ]);
                })

                ->action(function ($record, array $data) {
                    $detail = FeederDetail::where('bast_id', $record->bast_id)
                        ->where('feeder_name', $record->feeder_name)
                        ->first();

                    $oldNotes = $detail?->notes ?? '';
                    $newEntry = "[" . now() . "] " . Auth::user()->email . "-> Reject :\n" .
                                $data['notes'] . "\n\n";
                    $finalNotes = $newEntry ." | " . $oldNotes;

                    FeederDetail::where('bast_id', $record->bast_id)
                        ->where('feeder_name', $record->feeder_name)
                        ->update([
                            'status'       => 'rejected',
                            'approval_by'  => Auth::user()->email,
                            'approval_at'  => now(),
                            'notes'        => $finalNotes,
                        ]);
                })

                ->after(fn () => $this->dispatch('refresh'))
                ->successNotificationTitle('Data berhasil di-reject'),
            // Tables\Actions\ViewAction::make(),
            Action::make('export_implementation')
                    ->label('Print')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn ($record) => Excel::download(new BastFeederExport($record), "Implementation_Feeder_{$record->feeder_name}.xlsx"))
                    ->visible(fn ($record) => $record->status === 'approved'),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('export_implementation_feeder_bulk')
                ->label('Print Bulk Feeder')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(function (\Illuminate\Support\Collection $records) {
                    // filter hanya record approved
                    $records = $records->filter(fn($record) => $record->status === 'approved');

                    if ($records->isEmpty()) {
                        Notification::make()
                            ->title('Tidak ada record yang approved!')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Buat ZIP untuk beberapa Excel
                    $zipFileName = 'Implementation_Feeder_' . now()->format('Ymd_His') . '.zip';
                    $zipPath = storage_path($zipFileName);

                    $zip = new \ZipArchive();
                    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                        foreach ($records as $record) {
                            $excelFileName = "Implementation_Feeder_{$record->feeder_name}.xlsx";
                            $excelContent = Excel::raw(new BastFeederExport($record), \Maatwebsite\Excel\Excel::XLSX);
                            $zip->addFromString($excelFileName, $excelContent);
                        }
                        $zip->close();
                    }

                    return response()->download($zipPath)->deleteFileAfterSend(true);
                }),
        ];
    }


}
