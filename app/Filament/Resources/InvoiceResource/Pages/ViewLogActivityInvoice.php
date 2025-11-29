<?php
namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ViewLogActivityInvoice extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = InvoiceResource::class;

    // protected static string $view = 'filament::components.empty'; // TANPA BLADE
     protected static string $view = 'filament.pages.log-activity-invoice';

    public ?int $record = null;

    public function mount($record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return "Log Activity Invoice #{$this->record}";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()
                    ->where('menu', 'Invoice Items')
                    ->where('subject_id', $this->record)
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(60),

                Tables\Columns\TextColumn::make('email')
                    ->label('User'),

                Tables\Columns\TextColumn::make('event')
                    ->label('Aksi'),

                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->limit(50),
            ])
            ->defaultSort('id', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}