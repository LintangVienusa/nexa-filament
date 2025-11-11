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


class ListPoleDetails extends ListRecords
{
    use InteractsWithTable;
    protected static string $resource = BastProjectResource::class;

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
        return PoleDetail::query()
        ->when($this->bastId, fn($query) => $query->where('bast_id', $this->bastId));
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('bast_id')->label('BAST ID')->searchable(),
            TextColumn::make('pole_sn')->searchable(),
            TextColumn::make('notes')->searchable(),
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
            TextColumn::make('progress_percentage')->label('Progress (%)')->numeric()->sortable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            // Tables\Actions\ViewAction::make(),
            Action::make('export_implementation')
                    ->label('Tiang')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn ($record) => Excel::download(new BastPoleExport($record), "Implementation_{$record->kode}.xlsx")),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            // Tables\Actions\DeleteBulkAction::make(),
        ];
    }
}
