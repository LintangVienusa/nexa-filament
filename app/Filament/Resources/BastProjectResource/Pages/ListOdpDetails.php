<?php

namespace App\Filament\Resources\BastProjectResource\Pages;

use App\Filament\Resources\BastProjectResource;
use App\Models\BastProject;
use App\Models\ODPDetail;
use App\Models\Employee;
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
use App\Exports\BastODPExport;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Form as FilamentForm;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Actions\Action as HeaderAction;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\SelectFilter;

class ListOdpDetails extends ListRecords
{
    use InteractsWithTable;
    protected static string $resource = BastProjectResource::class;

    protected static ?string $title = 'List ODP Details';

    protected static ?string $navigationLabel = 'ODP Details';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $slug = 'list-odp-details';
    public ?string $bast_id = null;

    public function mount(?string $bast_id = null): void
    {
        $this->bast_id = $bast_id;
    }

    protected function getTableQuery(): Builder
    {
        return ODPDetail::query()
                ->Join('BastProject', 'ODPDetail.bast_id', '=', 'BastProject.bast_id')
                ->when($this->bast_id, fn($query) =>
                        $query->where('BastProject.bast_id', $this->bast_id)
                    )
                    ->select('ODPDetail.*', 'BastProject.bast_id');
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

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'ODPDetail.updated_at';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }


    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('bast_id')->label('BAST ID')->searchable(['BastProject.bast_id'])->sortable(),
            TextColumn::make('site')->label('Site')->searchable(['BastProject.site'])->sortable(),
            TextColumn::make('odc_name')->searchable(['ODPDetail.odc_name'])->sortable(),
            TextColumn::make('odp_name')->searchable(['ODPDetail.odp_name'])->sortable(),
            TextColumn::make('notes')->searchable(['ODPDetail.notes']),
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

            ImageColumn::make('power_optic_odc')
                ->label('Power Optic ODC')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->power_optic_odc ? asset('storage/'.$record->power_optic_odc) : null)
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
            TextColumn::make('employee.first_name')
                    ->label('Nama Petugas')
                    ->getStateUsing(fn ($record) => $record->employee?->full_name ?? '-')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereExists(function ($q) use ($search) {
                            $q->select(DB::raw(1))
                            ->from(DB::connection('mysql_employees')->getDatabaseName() . '.Employees')
                            ->whereColumn('Employees.email', 'ODPDetail.updated_by')
                            ->whereRaw(
                                "CONCAT(first_name,' ',middle_name,' ',last_name) LIKE ?",
                                ["%{$search}%"]
                            );
                        });
                    })
                    ->sortable(function (Builder $query) {
                        $direction = request()->input('tableSortDirection', 'asc');

                        return $query->orderBy(
                            Employee::selectRaw(
                                "CONCAT(first_name, ' ', middle_name, ' ', last_name)"
                            )
                            ->whereColumn('Employees.email', 'ODPDetail.updated_by')
                            ->limit(1),
                            $direction
                        );
                    }),
                TextColumn::make('updated_at')->sortable(),
        ];
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
                    view('filament.homepass.view-detail-odp', [
                        'record' => $record,
                    ])
                ),
            Action::make('maps')
                        ->label('Lihat Maps')
                        ->icon('heroicon-o-map')
                        ->url(fn ($record) => "https://www.google.com/maps?q={$record->latitude},{$record->longitude}")
                        ->openUrlInNewTab()
                        ->color('success'),
            // Action::make('pending')
            //     ->label('Pending')
            //     ->icon('heroicon-o-check-circle')
            //     ->color('warning')
            //     ->requiresConfirmation()
            //     ->visible(fn ($record) => $record->status === 'submit'  && (int) $record->progress_percentage >= 100)
            //     ->action(function ($record) {
            //         ODPDetail::where('bast_id', $record->bast_id)->where('odp_name', $record->odp_name)
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
                    ODPDetail::where('bast_id', $record->bast_id)->where('odp_name', $record->odp_name)
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

                ->mountUsing(function (FilamentForm  $form, $record) {
                    $detail = ODPDetail::where('bast_id', $record->bast_id)
                        ->where('odp_name', $record->odp_name)
                        ->first();

                    $form->fill([
                        'notes' => '',
                        'old_notes' => $detail?->notes,
                    ]);
                })

                ->action(function ($record, array $data) {
                    $detail = ODPDetail::where('bast_id', $record->bast_id)
                        ->where('odp_name', $record->odp_name)
                        ->first();

                    $oldNotes = $detail?->notes ?? '';
                    $newEntry = "[" . now() . "] " . Auth::user()->email . "-> Reject :\n" .
                                $data['notes'] . "\n\n";
                    $finalNotes = $newEntry  ." | ". $oldNotes;

                    ODPDetail::where('bast_id', $record->bast_id)
                        ->where('odp_name', $record->odp_name)
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
                    ->action(fn ($record) => Excel::download(new BastODPExport($record), "Implementation_ODP_{$record->odp_name}.xlsx"))
                    ->visible(fn ($record) => $record->status === 'approved'),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label('Status')
                ->options([
                    'submit'   => 'Submit',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->query(function (Builder $query, array $data) {
                    if (! $data['value']) {
                        return;
                    }

                    $query->where('ODPDetail.status', $data['value']);
                }),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
             BulkAction::make('export_implementation_odp_bulk')
            ->label('Print Bulk ODP')
            ->icon('heroicon-o-document-arrow-down')
            ->color('primary')
            ->action(function (\Illuminate\Support\Collection $records) {
                
                $records = $records->filter(fn($record) => $record->status === 'approved');

                if ($records->isEmpty()) {
                    Notification::make()
                        ->title('Tidak ada record yang approved!')
                        ->danger()
                        ->send();
                    return;
                }

                $zipFileName = 'Implementation_ODP_' . now()->format('Ymd_His') . '.zip';
                $zipPath = storage_path($zipFileName);

                $zip = new \ZipArchive();
                if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                    foreach ($records as $record) {
                        $excelFileName = "Implementation_ODP_{$record->odp_name}.xlsx";
                        $excelContent = Excel::raw(new BastODPExport($record), \Maatwebsite\Excel\Excel::XLSX);
                        $zip->addFromString($excelFileName, $excelContent);
                    }
                    $zip->close();
                }

                return response()->download($zipPath)->deleteFileAfterSend(true);
            }),
        ];
    }
}
