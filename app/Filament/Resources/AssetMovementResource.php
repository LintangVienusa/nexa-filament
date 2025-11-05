<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetMovementResource\Pages;
use App\Models\AssetMovement;
use App\Models\Asset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Traits\HasOwnRecordPolicy;
use Spatie\Permission\Traits\HasPermissions;
use App\Traits\HasNavigationPolicy;


class AssetMovementResource extends Resource
{
    
    use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;
    protected static ?string $model = AssetMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Asset Movements';
    protected static ?int $navigationSort = 0;

    public static function canCreate(): bool
    {
        return false; 
    }

    public static function canEdit($record): bool
    {
        return false; // atau logika sesuai role
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\Select::make('asset_id')
                            ->relationship('asset', 'name')
                            ->label('Asset')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('inventory_id')
                            ->relationship('inventory', 'name')
                            ->label('Inventory')
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('movement_id')
                            ->label('Movement ID')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Detail Pergerakan Asset')
                    ->schema([
                        Forms\Components\TextInput::make('PIC')
                            ->label('PIC ID')
                            ->numeric()
                            ->nullable(),

                        Forms\Components\TextInput::make('asset_qty_now')
                            ->label('Qty Sekarang')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\TextInput::make('request_asset_qty')
                            ->label('Qty Request')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                0 => 'Submit',
                                1 => 'Pending',
                                2 => 'Approved',
                                3 => 'Rejected',
                            ])
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('BA & Evidence')
                    ->schema([
                        Forms\Components\TextInput::make('ba_number')
                            ->label('BA Number'),

                        Forms\Components\Textarea::make('ba_description')
                            ->label('BA Description')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('file_path')
                            ->label('File BA')
                            ->directory('ba-documents')
                            ->nullable(),

                        Forms\Components\Textarea::make('evidence_return')
                            ->label('Evidence Pengembalian')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pihak Terkait')
                    ->schema([
                        Forms\Components\TextInput::make('handover_by')
                            ->label('Diserahkan oleh')
                            ->nullable(),

                        Forms\Components\TextInput::make('received_by')
                            ->label('Diterima oleh')
                            ->nullable(),

                        Forms\Components\TextInput::make('created_by')
                            ->label('Dibuat oleh')
                            ->default(fn () => auth()->user()?->email)
                            ->disabled(),
                    ])
                    ->columns(3),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('movement_id')->label('Movement ID')->searchable(),
                Tables\Columns\TextColumn::make('movementType')->label('Movement Type')->searchable(),
                Tables\Columns\TextColumn::make('placement_type')->label('Placement Type')->searchable(),
                Tables\Columns\TextColumn::make('asset.name')->label('Asset')->searchable(),
                Tables\Columns\TextColumn::make('asset.serialNumber')->label('Serial Number')->searchable(),
                Tables\Columns\TextColumn::make('PIC')->label('PIC'),
                Tables\Columns\TextColumn::make('recipient')->label('Penerima')->searchable(),
                Tables\Columns\TextColumn::make('location')->label('location')->searchable(),
                Tables\Columns\TextColumn::make('province_code')->label('Provinsi')->searchable(),
                Tables\Columns\TextColumn::make('regency_code')->label('Kabupaten')->searchable(),
                Tables\Columns\TextColumn::make('village_code')->label('Kota')->searchable(),
                Tables\Columns\TextColumn::make('status')->searchable()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        0 => 'Submit',
                        1 => 'Pending',
                        2 => 'Approved',
                        3 => 'Rejected',
                    })
                    ->badge()
                    ->colors([
                        'secondary' => 0,
                        'warning' => 1,
                        'success' => 2,
                        'danger' => 3,
                    ]),
                Tables\Columns\TextColumn::make('created_by')->label('Created By'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Created At'),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetMovements::route('/'),
            'create' => Pages\CreateAssetMovement::route('/create'),
            'edit' => Pages\EditAssetMovement::route('/{record}/edit'),
        ];
    }
}
