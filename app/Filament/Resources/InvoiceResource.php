<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Select;
use App\Services\DownloadInvoiceService;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Transactions';
    protected static ?int $navigationSort = 3;

    public static function mutateFormDataBeforeFill(array $data): array
    {
        $data['subtotal'] = $data['subtotal'] ?? 0;
        $data['tax_rate'] = $data['tax_rate'] ?? 0;
        $data['tax_amount'] = $data['tax_amount'] ?? 0;
        $data['amount'] = $data['amount'] ?? 0;

        return $data;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Invoice Information')
                    ->schema([
                        TextInput::make('invoice_number')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('customer_id')
                            ->hidden()
                            ->afterStateHydrated(function ($set, $state, $record) {
                                    if ($record && $record->customer_id) {
                                        $set('customer_name', \DB::table('Customers')->where('id', $record->customer_id)->value('customer_name'));
                                    }
                                }),
                        TextInput::make('customer_name')
                            ->label('Customer')
                            ->disabled(),
                        DatePicker::make('invoice_date')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Financial Details')
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->required()
                            ->prefix('Rp ')
                            ->reactive()
                            ->disabled()
                            ->formatStateUsing(fn($state) => $state ? number_format((int)$state, 0, ',', '.') : '')
                            ->dehydrateStateUsing(fn($state) => preg_replace('/,/', '', $state))
                            ->default(0.00),
                        TextInput::make('tax_rate')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $subtotal = (int) preg_replace('/[^0-9]/', '', $get('subtotal') ?? '0');

                                $rate = (float) $state / 100;

                                $taxAmount = round($subtotal * $rate);
                                $amount    = round($subtotal - $taxAmount);

                                $set('tax_amount',   number_format($taxAmount, 0, ',', '.'));
                                $set('amount',  number_format($amount, 0, ',', '.'));
                            })
                            ->formatStateUsing(fn ($state) => $state !== null ? $state * 100 : 0)
                            ->dehydrateStateUsing(fn ($state) => (float) $state / 100)

                            ->default(12),
                        TextInput::make('tax_amount')
                            ->required()
                            ->prefix('Rp ')
                            ->reactive()
                            ->readonly()
                            ->formatStateUsing(fn($state) => $state ? number_format((int)$state, 0, ',', '.') : '')
                            ->dehydrateStateUsing(fn($state) => preg_replace('/[^\d]/', '', $state))
                            ->default(0.00),
                        TextInput::make('amount')
                            ->required()
                            ->prefix('Rp ')
                            ->reactive()
                            ->readonly()
                            ->formatStateUsing(fn($state) => $state ? number_format((int)$state, 0, ',', '.') : '')
                            ->dehydrateStateUsing(fn($state) => preg_replace('/[^\d]/', '', $state))
                            ->default(0.00),
                    ])
                    ->columns(2),

                Section::make('Approval')
                    ->columns(2)
                    ->schema([
                        TextInput::make('create_by')
                            ->label('Created By')
                            ->disabled()
                            ->maxLength(255),

                        DateTimePicker::make('created_at')
                            ->label('Created At')
                            ->disabled()
                            ->displayFormat('d M Y H:i'),
                        Select::make('status')
                            ->label('Invoice Status')
                            ->options([
                                '0' => 'Draft',
                                '1' => 'Approved',
                                '2' => 'Paid',
                            ])
                            ->required()
                            ->default('0')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (in_array($state, ['1', '2'])) {
                                    $set('approval_by', auth()->user()->email);
                                    $set('approval_at', now());
                                } else {
                                    $set('approval_by', null);
                                    $set('approval_at', null);
                                }
                            }),

                        TextInput::make('approval_by')
                            ->label('Approved By')
                            ->readonly()
                            ->visible(fn ($get) => in_array($get('status'), ['1', '2'])),

                        DateTimePicker::make('approval_at')
                            ->label('Approved At')
                            ->readonly()
                            ->displayFormat('d M Y H:i')
                            ->visible(fn ($get) => in_array($get('status'), ['1', '2'])),
                        ]),
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                TextColumn::make('invoice_number')->label('Invoice Number')->sortable(),
                TextColumn::make('customer.customer_name')->label('Customer')->sortable(),
                TextColumn::make('subtotal')
                    ->label('subtotal')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => match($record->status) {
                        '0' => 'Draft ⏱️',
                        '1' => 'Approved ✅',
                        '2' => 'Paid 💵',
                        default => 'Unknown ❓',
                    })
                    ->colors([
                        '0' => 'secondary',
                        '1' => 'success',
                        '2' => 'primary',
                    ]),
                TextColumn::make('create_by')->label('Created By')->sortable(),
                TextColumn::make('created_at')->date()->label('Created At')->sortable(),
                TextColumn::make('approval_by')->label('Approval By')->sortable(),
                TextColumn::make('approval_at')->date()->label('Approval At')->sortable(),

            ])

            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn ($record) => static::downloadInvoice($record)),
                // Action::make('download')
                // ->label('Download Invoice')
                // ->icon('heroicon-o-arrow-down-tray')
                // ->action(function ($record, DownloadInvoiceService $service) {
                //     $pdf = $service->downloadInvoice($record);

                //             return response()->streamDownload(
                //                 fn () => print($pdf->output()),
                //                 "Invoice-{$record->id}.pdf"
                //             );
                // }),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static  function downloadInvoice($record)
    {
        $record->load('items.service', 'customer');

        $total = $record->items->sum(fn($i) => $i->subtotal);
        $taxRate = $record->tax_rate ?? 0.10;
        $taxrateper = $record->tax_rate * 100;
        $tax = $total * $taxRate;
        $grandTotal = $total + $tax;

        $spellNumber  = null;
        $spellNumber  = function ($number) use (&$spellNumber )  {
            $number = abs($number);
            $words  = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];

            if ($number < 12) {
                return " " . $words[$number];
            } elseif ($number < 20) {
                return $spellNumber ($number - 10) . " Belas";
            } elseif ($number < 100) {
                return $spellNumber (intval($number / 10)) . " Puluh" . $spellNumber ($number % 10);
            } elseif ($number < 200) {
                return " Seratus" . $spellNumber ($number - 100);
            } elseif ($number < 1000) {
                return $spellNumber (intval($number / 100)) . " Ratus" . $spellNumber ($number % 100);
            } elseif ($number < 2000) {
                return " Seribu" . $spellNumber ($number - 1000);
            } elseif ($number < 1000000) {
                return $spellNumber (intval($number / 1000)) . " Ribu" . $spellNumber ($number % 1000);
            } elseif ($number < 1000000000) {
                return $spellNumber (intval($number / 1000000)) . " Juta" . $spellNumber ($number % 1000000);
            } elseif ($number < 1000000000000) {
                return $spellNumber (intval($number / 1000000000)) . " Miliar" . $spellNumber (fmod($number, 1000000000));
            } else {
                return "Angka terlalu besar";
            }
        };

        $html = '
                <html>
                <head>
                     <style>
                        @page {
                            margin: 0;
                        }

                        html, body {
                            margin: 0;
                            padding: 0;
                            font-family: Arial, sans-serif;
                            background-color: #e8d1ad; /* coklat muda */
                        }

                        body {
                            padding: 40px;
                            position: relative;
                        }

                        .header {
                            display: flex;
                            justify-content: space-between;
                            align-items: flex-start;
                            margin-top: 0;
                            padding-top: 0;
                        }

                        .logo {
                            height: 100px;
                            margin-top: -10;
                            display: block;
                        }

                        .invoice-title {
                            font-family: Times New Roman, serif;
                            font-size: 40px;
                            font-weight: bold;
                            text-align: center;
                            display: flex;
                            align-items: center; 
                            justify-content: center;
                        }

                        .invoice-info {
                            margin-top: 20px;
                            background-color: #3a3a3a;
                            color: white;
                            padding: 10px 15px;
                            font-size: 14px;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            border-radius: 5px;
                        }

                        .invoice-info .number {
                            font-weight: bold;
                        }

                        .to-section {
                            margin-top: 10px;
                            display: flex;
                            justify-content: space-between;
                            font-size: 14px;
                        }

                        .to-section .left strong {
                            display: block;
                        }

                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 0;
                            font-size: 14px;
                        }

                        thead {
                            background-color: #3a3a3a;
                            color: white;
                        }

                        thead th {
                            padding: 10px;
                            text-align: left;
                        }

                        tbody td {
                            padding: 8px 10px;
                            border-bottom: 1px solid #ccc;
                        }

                        .total-box {
                            background-color: #3a3a3a;
                            color: white;
                            width: 200px;
                            padding: 10px;
                            margin-left: auto;
                            margin-top: 40px;
                            border-radius: 5px;
                            font-size: 12px;
                        }

                        .total-box div {
                            margin-bottom: 4px;
                        }

                        /* Watermark logo samar di background */
                        .watermark {
                            position: fixed;
                            top: 35%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                            opacity: 0.05;
                            z-index: 0;
                            width: 600px;
                        }
                    </style>
                </head>
                <body>
                    
                            <div class="header" style="display: flex; align-items: center; justify-content: space-between;  width: 100%; margin-top: 5px;">
                                <table>
                                    <td>
                                            <div class="logo-container" style="width: 240px; height: 180px; overflow: hidden; display: flex; align-items: center;">
                                                <img 
                                                    src="'. public_path('assets/images/Invoice Permit_20251008_233910_0001.png') .'" 
                                                    alt="Logo" 
                                                    style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                                            </div>
                                    </td>
                                    <td>
                                        <div class="title" style="text-align: right; flex-grow: 1; margin-left: 20px; margin-top: 5px;">
                                                <div style="font-size: 20px; font-weight: bold; text-transform: uppercase;">
                                                    Invoice
                                                </div>
                                                <div style="font-size: 12px; margin-top: 5px;">
                                                    This invoice is a valid proof of payment.
                                                </div>
                                            </div>
                                        
                                    </td>
                                </table>
                        </div>

                    <div  style="width: 50%;">
                        <table  class="info" >
                            <tr>
                                <td><strong>TOWER SEQUIS CENTER 9TH FLOOR NO. 902</strong></td>
                            </tr>
                            <tr>
                                <td>JL. JEND. SUDIRMAN 71, Kelurahan Senayan</td>
                            </tr>
                            
                            <tr>
                                <td>Kec. Kebayoran Baru, Adm. Jakarta Selatan</td>
                            </tr>
                            <tr>
                                <td>Provinsi DKI Jakarta, Kode Pos: 12190</td>
                            </tr>
                        </table>
                        
                    </div>

                    <div class="info">
                        <table style="width: 50%;">
                            <tr>
                                <td><strong>Nomor</strong></td>
                                <td>' . $record->invoice_number . '</td>
                            </tr>
                            <tr>
                                <td><strong>Date</strong></td>
                                <td>' . $record->created_at->format('d M Y') . '</td>
                            </tr>
                            <tr>
                                <td><strong>Customer</strong></td>
                                <td>' . $record->customer->customer_name . '</td>
                            </tr>
                            <tr>
                                <td><strong>Email Customer</strong></td>
                                <td>' . $record->customer->email . '</td>
                            </tr>
                        </table>
                    </div>

                    ' . ($record->status === "1" ? '
                        <div style="
                            position: absolute;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%) rotate(-45deg);
                            font-size: 80px;
                            color: rgba(10, 125, 218, 0.15);
                            font-style: italic;
                            text-align: center;
                            width: 100%;
                            z-index: 1000;
                            pointer-events: none;
                        ">
                            APPROVED
                        </div>
                        ' : ($record->status === "2" ? '
                            <div style="
                                position: absolute;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%) rotate(-45deg);
                                font-size: 80px;
                                color: rgba(9, 179, 52, 0.15);
                                font-style: italic;
                                text-align: center;
                                width: 100%;
                                z-index: 1000;
                                pointer-events: none;
                            ">
                                PAID
                            </div>
                        ' : '')) . '

                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Item</th>
                                <th>Unit Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>';

                foreach ($record->items as $index => $item) {
                    $html .= '<tr>
                                <td>' . ($index + 1) . '</td>
                                <td>' . $item->service->service_name . '</td>
                                <td style="text-align:right;">Rp ' . number_format($item->unit_price, 0, ',', '.') . '</td>
                                <td>' . $item->qty . '</td>
                                <td style="text-align:right;">Rp ' . number_format($item->subtotal, 0, ',', '.') . '</td>
                            </tr>';
                }

        $html .= '</tbody>
                        <tfoot>
                            <tr>
                            <td colspan="2" style="text-align:left; background-color:#f5f5f5;"></td>
                            <td colspan="2" style="text-align:left; background-color:#f5f5f5;">Subtotal</td>
                            <td style="text-align:right;">Rp ' . number_format($total, 0, ',', '.') . '</td>
                            </tr>
                            <tr>

                            <td colspan="2" style="text-align:left; background-color:#f5f5f5;"></td>
                            <td colspan="1" style="text-align:left; background-color:#f5f5f5;">Tax</td>
                            <td style="text-align:right;">' . $taxrateper . '%</td>
                            <td style="text-align:right;">Rp ' . number_format($tax, 0, ',', '.') . '</td>
                            </tr>
                            <tr>

                            <td colspan="2" style="text-align:left; background-color:#f5f5f5;"></td>
                            <td colspan="2" style="text-align:left; background-color:#f5f5f5;">Total Payment</td>
                            <td style="text-align:right;">Rp ' . number_format($grandTotal, 0, ',', '.') . '</td>
                            </tr>
                            <tr>
                            <td colspan="5" style="text-align:right; font-style:italic; font-size:12px;">
                                (** ' . trim($spellNumber ($grandTotal)) . ' Rupiah **)
                            </td>
                            </tr>
                            <tr>
                            <td colspan="5" style="text-align:left; font-style:italic; font-size:12px;">
                                BANK MANDIRI 1180014213705
                            </td>
                            </tr>
                            <tr>
                            <td colspan="5" style="text-align:left; font-style:italic; font-size:12px;">
                                 PT DAPOER POESAT NOESANTARA GROUP
                            </td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="footer">
                        Thank you for your business!
                    </div>
                </body>
                </html>';

        $pdf = \PDF::loadHTML($html)->setPaper('A4', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "Invoice-{$record->id}.pdf"
        );
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
