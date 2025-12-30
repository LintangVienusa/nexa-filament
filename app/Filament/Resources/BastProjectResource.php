<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BastProjectResource\Pages;
use App\Filament\Resources\BastProjectResource\RelationManagers;
use App\Models\BastProject;
use App\Models\MappingRegion;
use App\Models\Employee;
use App\Models\ODCDetail;
use App\Models\ODPDetail;
use App\Models\FeederDetail;
use App\Models\PoleDetail;
use App\Models\HomeConnect;
use App\Models\Purchaseorder;
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
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\DateFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\ColumnGroup;
use Illuminate\Support\Facades\DB;

class BastProjectResource extends Resource
{
    protected static ?string $model = BastProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function table(Table $table): Table
    {
        return $table
        
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('province_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('regency_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('village_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('station_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('project_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('po_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bast_id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('site')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('PIC')
                    ->label('PIC')
                    ->searchable()
                    ->sortable(),
                ColumnGroup::make('Homepass', [
                    TextColumn::make('pole_count')
                        ->label('Tiang')
                        ->getStateUsing(function ($record) {
                             $total = DB::connection('mysql_inventory')->table(DB::raw("
                                    ( SELECT bast_id, pole_sn  FROM PoleDetail
                                    WHERE bast_id = '{$record->bast_id}'
                                    GROUP BY bast_id, pole_sn ) as a
                                "))->count();

                            $completed = DB::connection('mysql_inventory')->table(DB::raw("
                                ( SELECT bast_id, pole_sn  FROM PoleDetail
                                WHERE bast_id = '{$record->bast_id}'
                                AND progress_percentage = 100
                                GROUP BY bast_id, pole_sn ) as a
                            "))->count();

                            return "{$completed} | {$total}";
                        })->alignRight()
                        ->url(fn($record) => url('/admin/bast-projects/list-pole-details/' . $record->bast_id))
                        ->openUrlInNewTab(true),
                    TextColumn::make('odc_count')
                        ->label('ODC')
                        ->getStateUsing(function ($record) {
                           

                             $total = DB::connection('mysql_inventory')->table(DB::raw("
                                    ( SELECT bast_id, odc_name  FROM ODCDetail
                                    WHERE bast_id = '{$record->bast_id}'
                                    GROUP BY bast_id, odc_name ) as a
                                "))->count();

                            $completed = DB::connection('mysql_inventory')->table(DB::raw("
                                ( SELECT bast_id, odc_name  FROM ODCDetail
                                WHERE bast_id = '{$record->bast_id}'
                                AND progress_percentage = 100
                                GROUP BY bast_id, odc_name ) as a
                            "))->count();

                            return "{$completed} | {$total}";
                        })
                        ->alignRight()
                        ->url(fn($record) => url('/admin/bast-projects/list-odc-details/' . $record->bast_id))
                        ->openUrlInNewTab(true),
                    TextColumn::make('odp_count')
                        ->label('ODP')
                        ->getStateUsing(function ($record) {
                            
                             $total = DB::connection('mysql_inventory')->table(DB::raw("
                                    ( SELECT bast_id, odp_name  FROM ODPDetail
                                    WHERE bast_id = '{$record->bast_id}'
                                    GROUP BY bast_id, odp_name ) as a
                                "))->count();

                            $completed = DB::connection('mysql_inventory')->table(DB::raw("
                                ( SELECT bast_id, odp_name  FROM ODPDetail
                                WHERE bast_id = '{$record->bast_id}'
                                AND progress_percentage = 100
                                GROUP BY bast_id, odp_name ) as a
                            "))->count();

                            return "{$completed} | {$total}";
                        })
                        ->alignRight()
                        ->url(fn($record) => url('/admin/bast-projects/list-odp-details/' . $record->bast_id))
                        ->openUrlInNewTab(true),
                    TextColumn::make('feeder')
                        ->label('Feeder')
                        ->getStateUsing(function ($record) {
                            

                             $total = DB::connection('mysql_inventory')->table(DB::raw("
                                    ( SELECT bast_id, feeder_name  FROM FeederDetail
                                    WHERE bast_id = '{$record->bast_id}'
                                    GROUP BY bast_id, feeder_name ) as a
                                "))->count();

                            $completed = DB::connection('mysql_inventory')->table(DB::raw("
                                ( SELECT bast_id, feeder_name  FROM FeederDetail
                                WHERE bast_id = '{$record->bast_id}'
                                AND progress_percentage = 100
                                GROUP BY bast_id, feeder_name ) as a
                            "))->count();

                            return "{$completed} | {$total}";
                        })
                        ->alignRight()
                        ->url(fn($record) => url('/admin/bast-projects/list-feeder-details/' . $record->bast_id))
                        ->openUrlInNewTab(true),
                ])->label('HOMEPASS')->alignCenter(),
                ColumnGroup::make('Homeconnect', [
                    TextColumn::make('homeconnect')
                        ->label('port')
                        ->getStateUsing(function ($record) {
                            $total = HomeConnect::where('bast_id', $record->bast_id)->count();

                            $completed = HomeConnect::where('bast_id', $record->bast_id)
                                ->where('progress_percentage', 100)
                                ->count();

                            return "{$completed} | {$total}";
                        })
                        ->alignRight()
                        ->url(fn($record) => url('/admin/bast-projects/list-home-connect-details/' . $record->bast_id))
                        ->openUrlInNewTab(true)
                ])->label('HOMECONNECT')->alignCenter(),
                TextColumn::make('bast_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->datetime()
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
                            ->options(fn() => BastProject::distinct()
                                ->whereNotNull('province_name')
                                ->pluck('province_name', 'province_name')
                                ->toArray()
                            )
                            ->reactive(),

                        Select::make('regency_name')
                            ->label('Regency')
                            ->options(function ($get) {
                                $province = $get('province_name');
                                return MappingRegion::when($province, fn($q) => $q->where('province_name', $province))
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
                                return MappingRegion::when($province, fn($q) => $q->where('province_name', $province))
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
                                return MappingRegion::when($province, fn($q) => $q->where('province_name', $province))
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
            ->actions([])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Region')
                    ->schema([
                        Select::make('province_name')
                            ->label('Kode Provinsi')
                            ->searchable()
                            ->options(MappingRegion::pluck('province_name', 'province_name'))
                            ->required()
                            ->reactive(),

                        Select::make('regency_name')
                            ->label('Kode Kabupaten')
                            ->searchable()
                            ->options(function (callable $get) {
                                $province = $get('province_name');
                                if (!$province) return [];
                                return MappingRegion::where('province_name', $province)
                                    ->pluck('regency_name', 'regency_name');
                            })
                            ->reactive()
                            ->required(),

                        Select::make('village_name')
                            ->label('Kode Desa')
                            ->searchable()
                            ->options(function (callable $get) {
                                $regency = $get('regency_name');
                                if (!$regency) return [];
                                return MappingRegion::where('regency_name', $regency)
                                    ->pluck('village_name', 'village_name');
                            })
                            ->required()->live(),

                        Select::make('station_name')
                            ->label('Stasiun')
                            ->searchable()
                            ->options(function (callable $get) {
                                $village_name = $get('village_name');
                                if (!$village_name) return [];
                                return MappingRegion::where('village_name', $village_name)
                                    ->pluck('station_name', 'station_name');
                            })
                            ->required()->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('site', $state);
                            }),
                    ])->columns(2),

                Section::make('Project Info')
                    ->schema([

                        TextInput::make('site')
                                ->maxLength(255)
                                ->reactive()
                                ->required(),
                        Hidden::make('bast_id')
                            ->label('BAST ID')
                            ->unique(ignoreRecord: true)
                            ->default(fn() => 'BA-' . now()->format('YmdH') . '-' . rand(1000, 9999))
                            ->dehydrateStateUsing(fn($state) => $state),
                        DatePicker::make('bast_date')->required()->default(now()),
                        Select::make('po_number')
                            ->label('No Purchase Order')
                            ->searchable()
                            ->reactive()
                            ->options(function (callable $get) {
                                $village_name = $get('village_name');

                                if (!$village_name) return [];
                                $usedPo = BastProject::whereNotNull('po_number')->where('village_name', $village_name)
                                            ->pluck('po_number');

                                return Purchaseorder::where('kecamatan', $village_name)->whereNotIn('po_number', $usedPo)
                                    ->pluck('po_number', 'po_number');
                            })
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => $state) 
                            ->dehydrated(fn ($state) => filled($state)) 
                            ->afterStateHydrated(function ($component, $state, $record) {
                                $component->state($record?->po_number); 
                            }),
                        Textarea::make('project_name')->required()->maxLength(255),
                        Select::make('PIC')
                            ->label('PIC')
                            ->options(
                                Employee::get()->mapWithKeys(fn($emp) => [
                                    $emp->email => $emp->full_name
                                ])
                            )
                            ->reactive()
                            ->searchable()
                            ->required()
                            ->default(fn($record) => $record?->email ?? auth()->user()->employee?->email
                            )
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $employee = Employee::find($state);
                                    $set('email', $employee?->email);
                                }
                            })
                            ->dehydrated(true),
                        Hidden::make('pass')
                            ->default('HOMEPASS')
                            ->dehydrated(true),
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
                            ->required(fn(callable $get) => $get('pass') === 'HOMEPASS')
                            ->visible(fn(callable $get) => $get('pass') === 'HOMEPASS')->dehydrated(true),

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
                            ->required(fn(callable $get) => $get('pass') === 'HOMEPASS')
                            ->visible(fn(callable $get) => $get('pass') === 'HOMEPASS')->dehydrated(true),
                    ])->columns(2)
                    ->visible(fn(callable $get) => $get('pass') === 'HOMEPASS'),
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
                            ->visible(fn(callable $get) => $get('pass') === 'HOMECONNECT')->dehydrated(true),
                    ])->columns(2)
                    ->visible(fn(callable $get) => $get('pass') === 'HOMECONNECT'),

                Section::make('Other Details')
                    ->schema([
                        Textarea::make('notes')->columnSpanFull(),
                    ]),
                Hidden::make('created_by')
                    ->default(fn() => Auth::user()->email)
                    ->dehydrated(true),
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
            'list-pole-details' => Pages\ListPoleDetails::route('/list-pole-details/{bast_id}'),
            'list-odc-details' => Pages\ListOdcDetails::route('/list-odc-details/{bast_id}'),
            'list-odp-details' => Pages\ListOdpDetails::route('/list-odp-details/{bast_id}'),
            'list-feeder-details' => Pages\ListFeederDetails::route('/list-feeder-details/{bast_id}'),
            'list-home-connect-details' => Pages\ListHomeConnectDetails::route('/list-home-connect-details/{bast_id}'),
        ];
    }
}
