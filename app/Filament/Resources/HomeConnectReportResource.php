<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeConnectReportResource\Pages;
use App\Filament\Resources\HomeConnectReportResource\RelationManagers;
use App\Models\HomeConnectReport;
use App\Models\Employee;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Hidden;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\HomeConnectReportExport;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Facades\DB;


class HomeConnectReportResource extends Resource
{
    protected static ?string $model = HomeConnectReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('bast_id')
                            ->label('BAST ID')
                            ->unique(ignoreRecord: true)
                            ->default(fn() => 'BA-' . now()->format('YmdH') . '-' . rand(1000, 9999))
                            // ->readonly()
                            ->dehydrateStateUsing(fn($state) => $state),
                Forms\Components\TextInput::make('updated_at')->label('Tanggal IKR'),
                Forms\Components\TextInput::make('updated_at')->label('Tanggal IKR'),
                Forms\Components\TextInput::make('id_pelanggan'),
                Forms\Components\TextInput::make('name_pelanggan'),

                Forms\Components\TextInput::make('site')
                    ->required(),

                Forms\Components\TextInput::make('odp_name'),

                Forms\Components\TextInput::make('port_odp'),

                Forms\Components\Select::make('status_port')
                    ->options([
                        'idle' => 'Idle',
                        'used' => 'Used',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('merk_ont'),
                Forms\Components\TextInput::make('sn_ont'),
                Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),

                Forms\Components\TextInput::make('progress_percentage')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),
                    
                //
            ])->columns(2);
            
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                //
                Tables\Columns\TextColumn::make('updated_at')->label('Tanggal IKR')->sortable(),
                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label('Nama Petugas')
                    ->getStateUsing(fn ($record) => $record->employee?->full_name ?? '-')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereExists(function ($q) use ($search) {
                            $q->select(DB::raw(1))
                            ->from(DB::connection('mysql_employees')->getDatabaseName() . '.Employees')
                            ->whereColumn('Employees.email', 'HomeConnect.updated_by')
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
                            ->whereColumn('Employees.email', 'home_connect_reports.updated_by')
                            ->limit(1),
                            $direction
                        );
                    }),
                Tables\Columns\TextColumn::make('id_pelanggan')
                ->formatStateUsing(fn ($state) => strtoupper($state))
                ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name_pelanggan')
                ->formatStateUsing(fn ($state) => strtoupper($state))
                ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sn_ont')->label('SN ONT')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('site')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('odp_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('port_odp')->searchable()->sortable(),
                // Tables\Columns\ImageColumn::make('foto_label_odp')
                // ->getStateUsing(fn ($record) => $record->foto_label_odp),
                ImageColumn::make('foto_label_odp')
                    ->label('Label ODP')
                    ->disk('public')
                    ->height(60)
                    ->width(60)
                    ->toggleable(),
                ImageColumn::make('foto_sn_ont')
                    ->label('SN ONT')
                    ->disk('public')
                    ->height(60)
                    ->width(60)
                    ->toggleable(),
                ImageColumn::make('foto_label_id_plg')
                    ->label('Label Pelanggan')
                    ->disk('public')
                    ->height(60)
                    ->width(60)
                    ->toggleable(),
                ImageColumn::make('foto_qr')
                    ->label('QR Pelanggan')
                    ->disk('public')
                    ->height(60)
                    ->width(60)
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status_port')
                    ->colors([
                        'success' => 'idle',
                        'danger' => 'used',
                    ]),
                Tables\Columns\TextColumn::make('progress_percentage')
                ->suffix('%')->sortable(),
                
            ])
            ->filters([
                Filter::make('updated_at')
                    ->form([
                       DatePicker::make('start_date')
                            ->label('Tanggal Mulai IKR')
                            ->placeholder('YYYY-MM-DD'),
                        DatePicker::make('end_date')
                            ->label('Tanggal Akhir IKR')
                            ->placeholder('YYYY-MM-DD'),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['start_date'])) {
                            $query->whereDate('updated_at', '>=', $data['start_date']);
                        }
                        if (!empty($data['end_date'])) {
                            $query->whereDate('updated_at', '<=', $data['end_date']);
                        }
                    })
                    ->label('Filter Tanggal IKR'),
                // SelectFilter::make('status_port')
                //     ->label('Status Port')
                //     ->options([
                //         'idle' => 'Idle',
                //         'used' => 'Used',
                //     ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_all')
                    ->label('Export All')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () =>
                        Excel::download(
                            new HomeConnectReportExport,
                            'HomeConnectReport.xlsx'
                        )
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('map')
                ->label('Map')
                ->icon('heroicon-o-map')
                ->url(fn ($record) =>
                    'https://www.google.com/maps?q=' . $record->latitude . ',' . $record->longitude
                )
                ->openUrlInNewTab()
                ->visible(fn ($record) =>
                    !is_null($record->latitude) && !is_null($record->longitude)
                ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status_port', 'used');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
    

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeConnectReports::route('/'),
            'create' => Pages\CreateHomeConnectReport::route('/create'),
            'edit' => Pages\EditHomeConnectReport::route('/{record}/edit'),
        ];
    }
}
