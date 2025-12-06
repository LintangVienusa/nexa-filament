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
use App\Exports\BastODPExport;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Form as FilamentForm;

class listOdpDetails extends ListRecords
{
    use InteractsWithTable;
    protected static string $resource = BastProjectResource::class;

    protected static ?string $title = 'List ODP Details';

    protected static ?string $navigationLabel = 'ODP Details';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $slug = 'list-odp-details';
    public ?string $site = null;

    public function mount(?string $site = null): void
    {
        $this->site = $site;
    }

    protected function getTableQuery(): Builder
    {
        return ODPDetail::query()
                ->Join('BastProject', 'ODPDetail.bast_id', '=', 'BastProject.bast_id')
                ->when($this->site, fn($query) => 
                        $query->where('BastProject.site', $this->site)
                    )
                    ->select('ODPDetail.*', 'BastProject.site');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('bast_id')->label('BAST ID')->searchable(['BastProject.bast_id']),
            TextColumn::make('site')->label('Site')->searchable(['BastProject.site']),
            TextColumn::make('odc_name')->searchable(['ODPDetail.odc_name']),
            TextColumn::make('odp_name')->searchable(['ODPDetail.odp_name']),
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
        ];
    }

    protected function getTableActions(): array
    {
        return [
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

    protected function getTableBulkActions(): array
    {
        return [
            // Tables\Actions\DeleteBulkAction::make(),
        ];
    }
}
