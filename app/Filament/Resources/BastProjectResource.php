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
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\DateFilter;
use Filament\Tables\Filters\Filter;

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

                        Select::make('station_name')
                            ->label('Stasiun')
                            ->searchable()
                            ->options(function(callable $get){
                                $village_name = $get('village_name');
                                if(!$village_name) return [];
                                return MappingRegion::where('village_name', $village_name)
                                    ->pluck('station_name','station_name');
                            })
                            ->required(),
                    ])->columns(2),

                Section::make('Project Info')
                    ->schema([
                        
                        TextInput::make('site')->maxLength(255),
                        Hidden::make('bast_id')
                            ->label('BAST ID')
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'BA-' . now()->format('YmdH') . '-' . rand(1000, 9999))
                            // ->readonly()
                            ->dehydrateStateUsing(fn ($state) => $state),
                        DatePicker::make('bast_date')->required()->default(now()),
                        Textarea::make('project_name')->required()->maxLength(255),
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
                        // Select::make('technici')
                        //     ->label('Teknisi')
                        //     ->options(
                        //         Employee::whereHas('Organization', fn($q) => $q->where('unit_name', 'Technician'))
                        //             ->get()
                        //             ->mapWithKeys(fn ($emp) => [
                        //                 $emp->email => $emp->full_name
                        //             ])
                        //     )
                        //     ->reactive()
                        //     ->searchable()
                        //     ->required()
                        //     ->dehydrated(true),
                        Select::make('pass')
                            ->options([
                                'HOMEPASS' => 'HOME PASS',
                                'HOMECONNECT' => 'HOME CONNECT',
                            ])
                            ->reactive()
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

                    Section::make('Upload Data Homepass')
                        ->schema([
                            Placeholder::make('photo')
                                ->label('Contoh Format Excel List Tiang')
                                 ->content(function () {
                                        $url = asset('assets/images/list_tiang_ct.jpg');
                                        return new HtmlString('<img src="' . $url . '" style="width:200px; border-radius:10px;">');
                                    }),

                            FileUpload::make('list_pole')
                                ->label('Upload Excel Tiang')
                                ->acceptedFileTypes([
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'application/octet-stream',
                                ])
                                ->directory('homepass_excels/tiang')
                                ->required(fn (callable $get) => $get('pass') === 'HOMEPASS')
                                ->visible(fn (callable $get) => $get('pass') === 'HOMEPASS')->dehydrated(true),
                            
                            Placeholder::make('photo')
                                ->label('Contoh Format Excel List FEEDER-ODC-ODP')
                                 ->content(function () {
                                        $url = asset('assets/images/list_feeder_odc_odp.jpg');
                                        return new HtmlString('<img src="' . $url . '" style="width:200px; border-radius:10px;">');
                                    }),

                            FileUpload::make('list_feeder_odc_odp')
                                ->label('Upload Excel FEEDER-ODC-ODP')
                                ->acceptedFileTypes([
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'application/octet-stream',
                                ])
                                ->directory('homepass_excels/feeder_odc_odp')
                                ->required(fn (callable $get) => $get('pass') === 'HOMEPASS')
                                ->visible(fn (callable $get) => $get('pass') === 'HOMEPASS')->dehydrated(true),
                        ])->columns(2)
                        ->visible(fn (callable $get) => $get('pass') === 'HOMEPASS'),
                    Section::make('Upload Data Homepass')
                        ->schema([
                            Placeholder::make('photo')
                                ->label('Contoh Format Excel List Homeconnect')
                                 ->content(function () {
                                        $url = asset('assets/images/list_homeconnect_new.jpg');
                                        return new HtmlString('<img src="' . $url . '" style="width:300px; border-radius:10px;">');
                                    }),
                            FileUpload::make('list_homeconnect')
                                ->label('Upload Excel Home Connect')
                                ->acceptedFileTypes([
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'application/octet-stream',
                                ])
                                ->directory('homeconnect_excels')
                                // ->required(fn (callable $get) => $get('pass') === 'HOMECONNECT')
                                ->visible(fn (callable $get) => $get('pass') === 'HOMECONNECT')->dehydrated(true),
                        ])->columns(2)
                        ->visible(fn (callable $get) => $get('pass') === 'HOMECONNECT'),

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
                TextColumn::make('province_name')
                    ->searchable(),
                TextColumn::make('regency_name')
                    ->searchable(),
                TextColumn::make('village_name')
                    ->searchable(),
                TextColumn::make('station_name')
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
                TextColumn::make('bast_date')
                    ->date()
                    ->sortable()
            ])
            ->filters([
                SelectFilter::make('status')
                        ->options([
                            'not started' => 'Not Started',
                            'in progress' => 'In Progress',
                            'pending' => 'Pending',
                            'completed' => 'Completed',
                        ])
                        ->label('Status'),

                Filter::make('province_regency_village_station')
            ->form([
                Select::make('province_name')
                    ->label('Province')
                    ->options(fn() => \App\Models\BastProject::distinct()
                        ->whereNotNull('province_name') 
                        ->pluck('province_name', 'province_name')
                        ->toArray()
                    )
                    ->reactive(),

                Select::make('regency_name')
                    ->label('Regency')
                    ->options(function ($get) {
                        $province = $get('province_name');
                        return \App\Models\MappingRegion::when($province, fn($q) => $q->where('province_name', $province))
                            ->whereNotNull('regency_name')
                            ->distinct()
                            ->pluck('regency_name', 'regency_name')
                            ->toArray();
                    })
                    ->reactive(),

                Select::make('village_name')
                    ->label('Village')
                    ->options(function ($get) {
                        $province = $get('province_name');
                        $regency = $get('regency_name');
                        return \App\Models\MappingRegion::when($province, fn($q) => $q->where('province_name', $province))
                            ->when($regency, fn($q) => $q->where('regency_name', $regency))
                            ->whereNotNull('village_name')
                            ->distinct()
                            ->pluck('village_name', 'village_name')
                            ->toArray();
                    })
                    ->reactive(),

                Select::make('station_name')
                    ->label('Station')
                    ->options(function ($get) {
                        $province = $get('province_name');
                        $regency = $get('regency_name');
                        $village = $get('village_name');
                        return \App\Models\MappingRegion::when($province, fn($q) => $q->where('province_name', $province))
                            ->when($regency, fn($q) => $q->where('regency_name', $regency))
                            ->when($village, fn($q) => $q->where('village_name', $village))
                            ->whereNotNull('station_name')
                            ->distinct()
                            ->pluck('station_name', 'station_name')
                            ->toArray();
                    })
                    ->reactive(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['province_name'] ?? null, fn($q, $v) => $q->where('province_name', $v))
                            ->when($data['regency_name'] ?? null, fn($q, $v) => $q->where('regency_name', $v))
                            ->when($data['village_name'] ?? null, fn($q, $v) => $q->where('village_name', $v))
                            ->when($data['station_name'] ?? null, fn($q, $v) => $q->where('station_name', $v));
                    }),

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
               Action::make('view_tiang')
                    ->label('Tiang')
                    ->icon('heroicon-o-eye')
                    ->visible(fn ($record) => $record->pass === 'HOMEPASS')
                    ->url(fn ($record) => url('/admin/bast-projects/list-pole-details/'.$record->bast_id))
                    ->openUrlInNewTab(true),

                Action::make('view_odc')
                    ->label('ODC')
                    ->icon('heroicon-o-eye')
                    ->visible(fn ($record) => $record->pass === 'HOMEPASS')
                    ->url(fn ($record) => url('/admin/bast-projects/list-odc-details/'.$record->bast_id))
                    ->openUrlInNewTab(true),

                Action::make('view_odp')
                    ->label('ODP')
                    ->icon('heroicon-o-eye')
                    ->visible(fn ($record) => $record->pass === 'HOMEPASS')
                    ->url(fn ($record) => url('/admin/bast-projects/list-odp-details/'.$record->bast_id))
                    ->openUrlInNewTab(true),
                Action::make('view_feeder')
                    ->label('FE')
                    ->icon('heroicon-o-eye')
                    ->visible(fn ($record) => $record->pass === 'HOMEPASS')
                    ->url(fn ($record) => url('/admin/bast-projects/list-feeder-details/'.$record->bast_id))
                    ->openUrlInNewTab(true),

                Action::make('view_rbs')
                    ->label('RBS')
                    ->icon('heroicon-o-eye')
                    ->visible(fn ($record) => $record->pass === 'HOMEPASS')
                    ->url(fn ($record) => url('/admin/bast-projects/list-rbs-details/'.$record->bast_id))
                    ->openUrlInNewTab(true),

                Action::make('view_homeconnect')
                    ->label('Home Connect')
                    ->icon('heroicon-o-eye')
                    ->visible(fn ($record) => $record->pass === 'HOMECONNECT')
                    ->url(fn ($record) => url('/admin/bast-projects/list-homeconnect-details/'.$record->bast_id))
                    ->openUrlInNewTab(true),
                // Action::make('export_implementation')
                //     ->label('Tiang')
                //     ->icon('heroicon-o-document-arrow-down')
                //     ->action(fn ($record) => Excel::download(new BastPoleExport($record), "Implementation_{$record->kode}.xlsx")),
        
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),s
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
            'list-pole-details' => Pages\ListPoleDetails::route('/list-pole-details/{record}'),
            'list-odc-details' => Pages\ListOdcDetails::route('/list-odc-details/{record}'),
            'list-odp-details' => Pages\ListOdpDetails::route('/list-odp-details/{record}'),
            'list-feeder-details' => Pages\ListFeederDetails::route('/list-feeder-details/{record}'),
            'list-rbs-details' => Pages\ListRbsDetails::route('/list-rbs-details/{record}'),
            'list-homeconnect-details' => Pages\ListHomeConnectDetails::route('/list-homeconnect-details/{record}'),
        ];
    }
}
