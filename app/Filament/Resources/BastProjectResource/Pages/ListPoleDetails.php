<?php

namespace App\Filament\Resources\BastProjectResource\Pages;

use App\Filament\Resources\BastProjectResource;
use App\Models\BastProject;
use App\Models\PoleDetail;
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


class ListPoleDetails extends ListRecords
{
    use InteractsWithTable;
    protected static string $resource = BastProjectResource::class;
    protected $connection = 'mysql_inventory';
    protected static ?string $title = 'List Pole Details';

    protected static ?string $navigationLabel = 'Pole Details';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $slug = 'list-pole-details';
     public ?int $bastId = null;
     public function mount(?string $bastId = null): void
    {
        $this->bastId = $bastId;
    }

    protected function getTableQuery(): Builder
    {
        return PoleDetail::on('mysql_inventory')
            ->with('bastProject')
            ->when($this->bastId, fn ($query) =>
                $query->where('bast_id', $this->bastId)
            );
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('bast_id')->label('BAST ID')->searchable(),
            TextColumn::make('site')->label('Site')->searchable(),
            TextColumn::make('pole_sn')->searchable(),
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
            Action::make('pending')
                ->label('Pending')
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === 'submit'  && (int) $record->progress_percentage >= 100)
                ->action(function ($record) {
                    PoleDetail::where('bast_id', $record->bast_id)->where('pole_sn', $record->pole_sn)
                    ->update([
                        'status'       => 'pending',
                        'approval_by'  => Auth::user()->email,
                        'approval_at'  => now(),
                    ]);
                })->after(fn () => $this->dispatch('refresh'))
                ->successNotificationTitle('Data berhasil di-pending'),
            Action::make('approve')
                ->label('Approved')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status !== 'approved' && (int) $record->progress_percentage >= 100)
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
                ->icon('heroicon-o-check-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === 'submit'  && (int) $record->progress_percentage >= 100)
                ->action(function ($record) {
                    PoleDetail::where('bast_id', $record->bast_id)->where('pole_sn', $record->pole_sn)
                    ->update([
                        'status'       => 'rejected',
                        'approval_by'  => Auth::user()->email,
                        'approval_at'  => now(),
                    ]);
                })->after(fn () => $this->dispatch('refresh'))
                ->successNotificationTitle('Data berhasil di-reject'),
            // Tables\Actions\ViewAction::make(),
            Action::make('export_implementation')
                    ->label('Print')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn ($record) => Excel::download(new BastPoleExport($record), "Implementation_Pole_{$record->site}.xlsx"))
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
