<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BastProjectResource\Pages;
use App\Filament\Resources\BastProjectResource\RelationManagers;
use App\Models\BastProject;
use App\Models\MappingRegion;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use App\Exports\BastPoleExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\Action;

class BastProjectResource extends Resource
{
    protected static ?string $model = BastProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Region')
                    ->schema([
                        Select::make('province_name')
                            ->label('Kode Provinsi')
                            ->searchable()
                            ->options(MappingRegion::pluck('province_name','province_name'))
                            ->required()
                            ->reactive(),

                        Select::make('regency_name')
                            ->label('Kode Kabupaten')
                            ->searchable()
                            ->options(function(callable $get){
                                $province = $get('province_name');
                                if(!$province) return [];
                                return MappingRegion::where('province_name', $province)
                                    ->pluck('regency_name','regency_name');
                            })
                            ->reactive()
                            ->required(),

                        Select::make('village_name')
                            ->label('Kode Desa')
                            ->searchable()
                            ->options(function(callable $get){
                                $regency = $get('regency_name');
                                if(!$regency) return [];
                                return MappingRegion::where('regency_name', $regency)
                                    ->pluck('village_name','village_name');
                            })
                            ->required(),
                    ])->columns(2),

                Section::make('Project Info')
                    ->schema([
                        TextInput::make('bast_id')
                            ->label('BAST ID')
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'BA-' . now()->format('YmdH') . '-' . rand(1000, 9999))
                            ->readonly()
                            ->dehydrateStateUsing(fn ($state) => $state),
                        DatePicker::make('bast_date')->required()->default(now()),
                        Textarea::make('project_name')->required()->maxLength(255),
                        TextInput::make('site')->maxLength(255),
                        Select::make('PIC')
                                ->label('PIC')
                                ->options(
                                    Employee::get()->mapWithKeys(fn ($emp) => [
                                        $emp->email => $emp->full_name
                                    ])
                                )
                                ->reactive()
                                ->searchable()
                                ->required()
                                ->default(fn ($record) =>
                                    $record?->email ?? auth()->user()->employee?->email
                                ) 
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state) {
                                        $employee = Employee::find($state);
                                        $set('email', $employee?->email);
                                    }
                                })
                                ->dehydrated(true),
                        Select::make('technici')
                            ->label('Teknisi')
                            ->options(
                                Employee::whereHas('Organization', fn($q) => $q->where('unit_name', 'Technician'))
                                    ->get()
                                    ->mapWithKeys(fn ($emp) => [
                                        $emp->email => $emp->full_name
                                    ])
                            )
                            ->reactive()
                            ->searchable()
                            ->required()
                            ->dehydrated(true),
                        Select::make('pass')
                            ->options([
                                'HOMEPASS' => 'HOMEPASS',
                                'HOMECONNECT' => 'HOMECONNECT',
                            ])
                            ->default('not started')
                            ->required(),
                        Select::make('status')
                            ->options([
                                'not started' => 'Not Started',
                                'in progress' => 'In Progress',
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                            ])
                            ->default('not started')
                            ->required(),
                        
                    ])->columns(2),

                Section::make('Other Details')
                    ->schema([
                        Textarea::make('notes')->columnSpanFull(),
                    ]),
                Hidden::make('created_by')
                        ->default(fn () => Auth::user()->email)
                        ->dehydrated(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bast_id')
                    ->searchable(),
                TextColumn::make('province_name')
                    ->searchable(),
                TextColumn::make('regency_name')
                    ->searchable(),
                TextColumn::make('village_name')
                    ->searchable(),
                TextColumn::make('project_name')
                    ->searchable(),
                TextColumn::make('site')
                    ->searchable(),
                TextColumn::make('PIC')
                    ->label('PIC')
                    ->searchable(),
                TextColumn::make('technici')
                    ->label('Teknisi')
                    ->searchable(), 
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn($state) => match($state) {
                        'not started' => 'Not Started',
                        'in progress' => 'In Progress',
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'not started' => 'gray',
                        'in progress' => 'info',
                        'pending' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('progress_percentage')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bast_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->searchable(),
                TextColumn::make('updated_by')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('export_implementation')
                    ->label('Export Implementation Sheet')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn ($record) => Excel::download(new BastPoleExport($record), "Implementation_{$record->kode}.xlsx")),
        
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBastProjects::route('/'),
            'create' => Pages\CreateBastProject::route('/create'),
            'edit' => Pages\EditBastProject::route('/{record}/edit'),
        ];
    }
}
