<?php

namespace App\Filament\Resources\BastProjectResource\Pages;

use App\Filament\Resources\BastProjectResource;
use App\Models\BastProject;
use App\Models\PoleDetail;
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
use App\Exports\BastPoleExport;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\ProgressColumn;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Form as FilamentForm;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Actions\Action as HeaderAction;
use Filament\Tables\Filters\SelectFilter;


class ListPoleDetails extends ListRecords
{
    use InteractsWithTable;
    protected static string $resource = BastProjectResource::class;
    protected $connection = 'mysql_inventory';
    protected static ?string $title = 'List Pole Details';

    protected static ?string $navigationLabel = 'Pole Details';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $slug = 'list-pole-details';
     public ?string $bast_id = null;
     public function mount(?string $bast_id = null): void
    {
        $this->bast_id = $bast_id;
    }

    protected function getTableQuery(): Builder
    {
        // return PoleDetail::on('mysql_inventory')
        //     ->with('bastProject')
        //     ->when($this->bast_id, fn ($query) =>
        //         $query->where('bast_id', $this->bast_id)
        //     );

        $sub = PoleDetail::selectRaw('bast_id,pole_sn, MAX(progress_percentage) as max_progress')
            ->groupBy( 'bast_id','pole_sn');

        return PoleDetail::query()
            ->join('BastProject', 'PoleDetail.bast_id', '=', 'BastProject.bast_id')
            ->joinSub($sub, 't', function ($join) {
                $join->on('t.bast_id', '=', 'PoleDetail.bast_id')
                ->on('t.pole_sn', '=', 'PoleDetail.pole_sn');
            })
            ->when($this->bast_id, fn($query) => 
                $query->where('BastProject.bast_id', $this->bast_id)
            )
            ->whereColumn('PoleDetail.progress_percentage', 't.max_progress')
            ->select('PoleDetail.*', 'BastProject.bast_id','BastProject.Site', 't.max_progress');
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
        return 'PoleDetail.updated_at';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('bast_id')->label('BAST ID')->searchable()->sortable(),
            TextColumn::make('site')->label('Site')->searchable()->sortable(),
            TextColumn::make('pole_sn')->searchable()->sortable(),
            TextColumn::make('notes')->searchable(),
            ImageColumn::make('digging')
                ->label('digging')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->digging ? asset('storage/'.$record->digging) : null)
                ->width(150)
                ->height(150),
            ImageColumn::make('instalasi')
                ->label('Instalasi')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->instalasi ? asset('storage/'.$record->instalasi) : null)
                ->width(150)
                ->height(150),
            ImageColumn::make('coran')
                ->label('Coran')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->coran ? asset('storage/'.$record->coran) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('tiang_berdiri')
                ->label('Tiang Berdiri')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->tiang_berdiri ? asset('storage/'.$record->tiang_berdiri) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('labeling_tiang')
                ->label('Labeling Tiang')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->labeling_tiang ? asset('storage/'.$record->labeling_tiang) : null)
                ->width(150)
                ->height(150),

            ImageColumn::make('aksesoris_tiang')
                ->label('Aksesoris Tiang')
                ->disk('public')
                ->getStateUsing(fn($record) => $record->aksesoris_tiang ? asset('storage/'.$record->aksesoris_tiang) : null)
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
                            ->whereColumn('Employees.email', 'PoleDetail.updated_by')
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
                            ->whereColumn('Employees.email', 'PoleDetail.updated_by')
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
                ->modalHeading('Detail Home Pass Pole')
                ->modalWidth('3xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(fn ($record) =>
                    view('filament.homepass.view-detail-pole', [
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
            //         PoleDetail::where('bast_id', $record->bast_id)->where('pole_sn', $record->pole_sn)
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
                    PoleDetail::where('bast_id', $record->bast_id)->where('pole_sn', $record->pole_sn)
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
                    $detail = PoleDetail::where('bast_id', $record->bast_id)
                        ->where('pole_sn', $record->pole_sn)
                        ->first();

                    
                    $form->fill([
                        'notes' => '',
                        'old_notes' => $detail?->notes, 
                    ]);
                })

                ->action(function ($record, array $data) {
                    $detail = PoleDetail::where('bast_id', $record->bast_id)
                        ->where('pole_sn', $record->pole_sn)
                        ->first();

                    $oldNotes = $detail?->notes ?? '';
                    $newEntry = "[" . now() . "] " . Auth::user()->email . "-> Reject :\n" .
                                $data['notes'] . "\n\n";
                    $finalNotes = $newEntry ." | " . $oldNotes;

                    PoleDetail::where('bast_id', $record->bast_id)
                        ->where('pole_sn', $record->pole_sn)
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
                    ->action(fn ($record) => Excel::download(new BastPoleExport($record), "Implementation_Pole_{$record->pole_sn}.xlsx"))
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

                    $query->where('FeederDetail.status', $data['value']);
                }),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('export_implementation_pole_bulk')
            ->label('Print Bulk Pole')
            ->icon('heroicon-o-document-arrow-down')
            ->color('primary')
            ->action(function (\Illuminate\Support\Collection $records) {
                // filter record yang approved
                $records = $records->filter(fn($record) => $record->status === 'approved');

                if ($records->isEmpty()) {
                    Notification::make()
                        ->title('Tidak ada record yang approved!')
                        ->danger()
                        ->send();
                    return;
                }

                // Buat ZIP untuk beberapa Excel
                $zipFileName = 'Implementation_Pole_' . now()->format('Ymd_His') . '.zip';
                $zipPath = storage_path($zipFileName);

                $zip = new \ZipArchive();
                if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                    foreach ($records as $record) {
                        $excelFileName = "Implementation_Pole_{$record->pole_sn}.xlsx";
                        $excelContent = Excel::raw(new BastPoleExport($record), \Maatwebsite\Excel\Excel::XLSX);
                        $zip->addFromString($excelFileName, $excelContent);
                    }
                    $zip->close();
                }

                return response()->download($zipPath)->deleteFileAfterSend(true);
            }),
        ];
    }
}
