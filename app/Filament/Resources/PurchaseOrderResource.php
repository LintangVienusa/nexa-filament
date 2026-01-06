<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use App\Models\MappingRegion;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Traits\HasOwnRecordPolicy;
use Spatie\Permission\Traits\HasPermissions;
use App\Traits\HasNavigationPolicy;

class PurchaseOrderResource extends Resource
{
    use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Purchase Order Info')
                    ->schema([
                        TextInput::make('po_number')
                            ->label('No Purchase Order')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Nomor PO sudah digunakan, silakan inputkan ulang PO yang belum digunakan.',
                            ])
                            ->maxLength(50)
                            ->disabledOn('edit'),

                        DatePicker::make('order_date')
                            ->default(now())
                            ->required(),

                        TextInput::make('po_issuer')
                            ->label('Penerbit Purchase Order')
                            ->required(),

                        Select::make('po_status')
                            ->label('Status')
                            ->default('draft')
                            ->options([
                                'draft' => 'Draft',
                                'issued' => 'Issued',
                                'closed' => 'Closed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Project Info')
                    ->schema([
                        Select::make('site_name')
                            ->label('Nama Site')
                            ->searchable()
                            ->options(fn() => MappingRegion::pluck('station_name', 'station_name'))
                            ->required()
                            ->dehydrateStateUsing(fn($state) => $state)
                            ->dehydrated(fn($state) => filled($state))
                            ->afterStateHydrated(function ($component, $state, $record) {
                                $component->state($record?->site_name);
                            }),
                        Select::make('kecamatan')
                            ->label('Kecamatan')
                            ->searchable()
                            ->options(function (callable $get) {
                                $station_name = $get('site_name');
                                if (!$station_name) return [];
                                return MappingRegion::where('station_name', $station_name)
                                    ->pluck('village_name', 'village_name');
                            })
                            ->required(),
                        Select::make('job_type')
                            ->label('Jenis Pekerjaan')
                            ->options([
                                'homepass' => 'Infrastruktur / Homepass',
                                'home_connect' => 'Home Connect / Managed Service',
                            ])
                            ->required(),

                        TextInput::make('total_target')
                            ->required()
                            ->numeric(),

                        DatePicker::make('project_start_date')
                            ->label('Tanggal Mulai Pekerjaan')
                            ->required(),
                        DatePicker::make('project_end_date')
                            ->label('Tanggal Selesai Pekerjaan')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Vendor & PIC')
                    ->schema([
                        TextInput::make('vendor')->required(),
                        TextInput::make('pic_name')
                            ->label('Nama PIC')
                            ->required(),
                        TextInput::make('pic_mobile_no')
                            ->label('No. HP PIC')
                            ->required()
                            ->maxLength(15)
                            ->rule('regex:/^[0-9]+$/')
                            ->placeholder('081234567890')
                            ->mask('999999999999999'),
                        TextInput::make('pic_email')
                            ->email(),
                    ])
                    ->columns(2),
                Section::make('Project Info')
                    ->schema([
                        Select::make('payment_terms')
                            ->options([
                                'dp' => 'DP',
                                'termin' => 'Termin',
                                'bulanan' => 'Bulanan',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->searchable(),

                TextColumn::make('po_issuer'),

                TextColumn::make('order_date')
                    ->date(),

                TextColumn::make('vendor')
                    ->searchable(),

                TextColumn::make('site_name')
                    ->searchable(),

                TextColumn::make('po_status')
                    ->badge()
                    ->colors([
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                        'closed' => 'Closed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Columns\TextColumn::make('total_target')
                    ->numeric()
                    ->alignRight(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('po_status')
                    ->options([
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                        'closed' => 'Closed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([])
            ->bulkActions([]);
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
