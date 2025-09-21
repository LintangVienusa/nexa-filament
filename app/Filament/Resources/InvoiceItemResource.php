<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceItemResource\Pages;
use App\Filament\Resources\InvoiceItemResource\RelationManagers;
use App\Models\InvoiceItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextInput\Mask;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\TextColumn\Badge;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\HasManyRepeater;
use Carbon\Carbon;

class InvoiceItemResource extends Resource
{
    protected static ?string $model = InvoiceItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Transactions';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
               Section::make('Invoice')
                    ->description('Select or confirm the invoice date.')
                    ->schema([
                       DatePicker::make('invoice_date')
                            ->label('Invoice Date')
                            ->default(now())
                            ->required(),
                    ])
                    ->columns(1),
               Section::make('Customer')
                    ->description('Select the customer for this invoice item.')
                    ->schema([
                       Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'customer_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        
                    ])
                    ->columns(1),
                
               Section::make('Invoice Items')
                    ->description('Add items for this invoice')
                    ->schema([
                       Repeater::make('items')
                            ->schema([

                               Section::make('Service Details')
                                    ->description('Select a service and provide additional details.')
                                    ->schema([
                                       Select::make('service_id')
                                            ->label('Service')
                                            ->relationship('service', 'service_name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $service = \App\Models\Service::find($state);
                                                    if ($service) {
                                                        $set('price',  number_format($service->price, 0, ',', '.'));
                                                        $set('qty', (int)$service->unit);
                                                        $set('subtotal', number_format($service->price, 0, ',', '.'));
                                                    }
                                                }
                                                }),

                                       TextInput::make('description')
                                            ->label('Description')
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),
                               TextInput::make('price')
                                    ->label('Unit Price')
                                    ->prefix('Rp')
                                    ->formatStateUsing(fn($state) => $state !== null ? number_format((int)$state, 0, ',', '.') : '0')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $number =(int) preg_replace('/[^0-9]/', '', $get('price') ?? '0');
                                        $set('price', $number ? number_format((int) $number, 0, ',', '.') : 0);
                                    })
                                    ->dehydrateStateUsing(fn($state) => (int) preg_replace('/[^0-9]/', '', (string) $state))
                                    ->required()
                                    ->reactive(),

                               TextInput::make('qty')
                                    ->required()
                                    ->numeric()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $number =(int) preg_replace('/[^0-9]/', '', $get('price') ?? '0');
                                        $subtotal = (int)$get('qty') * $number;
                                        $set('subtotal',  number_format($subtotal, 0, ',', '.'));
                                    }),

                               TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->prefix('Rp')
                                    ->readonly()
                                    ->formatStateUsing(fn($state) => $state !== null ? number_format((int)$state, 0, ',', '.') : '0')
                                    ->reactive()
                                    ->dehydrateStateUsing(fn($state) => (int) preg_replace('/[^0-9]/', '', (string) $state))
                                    ->required(),
                            ])
                            ->columns(3)
                    ])
                    ->columns(1),

                    

            ]);
    }
    

    public static function table(Table $table): Table
    {
        return $table
        ->query(InvoiceItem::UniqueInvoiceItem())
            ->columns([
                Split::make([
                    TextColumn::make('invoice_id')
                        ->label('Invoice ID')
                        ->numeric()
                        ->sortable(),
                    TextColumn::make('invoice.invoice_number')
                        ->label('Invoice ID')
                        ->numeric()
                        ->sortable(),

                    TextColumn::make('invoice.customer.customer_name')
                        ->label('Customer')
                        ->sortable()
                        ->searchable(),

                    TextColumn::make('invoice.created_at')
                        ->label('Created At')
                        ->dateTime()
                        ->sortable(),

                    
                ]),
                Panel::make([
                TextColumn::make('items')
                        ->label('Invoice Items')
                        ->getStateUsing(function ($record) {
                            return InvoiceItemResource::renderItemsPanel($record->invoice_id);
                        })
                        ->html()
                ])
                ->collapsible()
                ->collapsed(true)
                ->columnSpanFull()
                ->extraAttributes(['class' => '!max-w-none w-full']),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function renderItemsPanel($invoiceId)
    {
        $items = \App\Models\InvoiceItem::with('service')
            ->where('invoice_id', $invoiceId)
            ->get();

        if ($items->isEmpty()) {
            return "<div class='text-gray-500'>No items</div>";
        }

        $total = $items->sum('subtotal');

        $html = '<table class="w-full text-left border-collapse table-auto">';
        $html .= '<thead>
                    <tr>
                        <th class="px-4 py-2 text-sm border-none">Service</th>
                        <th class="px-4 py-2 text-sm border-none text-right">Qty</th>
                        <th class="px-4 py-2 text-sm border-none text-right">Unit Price</th>
                        <th class="px-4 py-2 text-sm border-none text-right">Subtotal</th>
                    </tr>
                </thead><tbody>';

        foreach ($items as $item) {
            $serviceName = $item->service->service_name ?? '-';
            $qty = $item->qty;
            $unitPrice = 'Rp ' . number_format($item->unit_price, 0, ',', '.');
            $subtotal = 'Rp ' . number_format($item->subtotal, 0, ',', '.');

            $html .= "<tr class='hover:bg-gray-50 transition'>
                        <td class='px-4 py-2 border-none'>{$serviceName}</td>
                        <td class='px-4 py-2 border-none text-right'>{$qty}</td>
                        <td class='px-4 py-2 border-none text-right'>{$unitPrice}</td>
                        <td class='px-4 py-2 border-none text-right'>{$subtotal}</td>
                    </tr>";
        }

        $html .= "<tr class='font-semibold'>
                    <td class='px-4 py-2 border-none'>Total</td>
                    <td class='border-none'></td>
                    <td class='border-none'></td>
                    <td class='px-4 py-2 border-none text-right'>Rp " . number_format($total, 0, ',', '.') . "</td>
                    <td class='border-none'></td>
                </tr>";

        $html .= '</tbody></table>';

        return $html;
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
            'index' => Pages\ListInvoiceItems::route('/'),
            'create' => Pages\CreateInvoiceItem::route('/create'),
            'edit' => Pages\EditInvoiceItem::route('/{record}/edit'),
        ];
    }
}
