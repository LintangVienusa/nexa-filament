<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
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

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Purchase Order Info')
                    ->schema([
                        Forms\Components\TextInput::make('po_number')
                            ->label('No Purchase Order')
                            ->required()
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
                        TextInput::make('site_name')
                            ->label('Nama Site')
                            ->required(),
                        TextInput::make('kecamatan'),
                        Select::make('job_type')
                            ->label('Jenis Pekerjaan')
                            ->options([
                                'infrastruktur' => 'Infrastruktur',
                                'homepass' => 'Homepass',
                                'home_connect' => 'Home Connect',
                                'managed_service' => 'Managed Service',
                            ])
                            ->required(),

                        TextInput::make('total_target')
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
                            ->required(),
                        TextInput::make('pic_email')
                            ->email(),
                    ])
                    ->columns(2),

                Select::make('payment_terms')
                    ->options([
                        'dp' => 'DP',
                        'termin' => 'Termin',
                        'bulanan' => 'Bulanan',
                    ])
                    ->required(),
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
