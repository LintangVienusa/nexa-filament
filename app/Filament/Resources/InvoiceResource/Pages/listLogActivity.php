<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Pages\Page;
use App\Models\LogActivity;

class listLogActivity extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected static ?string $title = 'Log Activity Invoice';

    public $invoiceId;

    public function mount(): void
    {
        $this->invoiceId = request()->route('record');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LogActivity::query()
                    ->whereIn('menu', ['Invoice'])
                    ->when($this->invoiceId, fn($q) => $q->where('subject_id', $this->invoiceId))
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('description')->label('Deskripsi')->limit(60),
                Tables\Columns\TextColumn::make('email')->label('User'),
                Tables\Columns\TextColumn::make('event')->label('Action'),
            ])
            ->defaultSort('id', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
