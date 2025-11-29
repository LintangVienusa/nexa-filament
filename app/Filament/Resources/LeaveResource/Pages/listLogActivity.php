<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Pages\Page;
use App\Models\LogActivity;

class listLogActivity extends ListRecords
{
    protected static string $resource = LeaveResource::class;

    protected static ?string $title = 'Log Activity Leave';

    public $leaveId;

    public function mount(): void
    {
        $this->leaveId = request()->route('record'); 
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LogActivity::query()
                    ->whereIn('menu', ['Leaves'])
                    ->when($this->leaveId, fn($q) => $q->where('subject_id', $this->leaveId))
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
