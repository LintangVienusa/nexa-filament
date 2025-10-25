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
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
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
                TextColumn::make('items.po_number')
                    ->label('PO Number')
                    ->sortable(),

                TextColumn::make('items.po_description')
                    ->label('PO Description')
                    ->sortable(),
                TextColumn::make('invoice_number')->label('Invoice Number')->sortable(),
                TextColumn::make('customer.customer_name')->label('Customer')->sortable(),
                TextColumn::make('subtotal')
                    ->label('subtotal')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('dp')
                    ->label('DP 20%')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('tax_amount')
                    ->label('tax 11%')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => match($record->status) {
                        '0' => 'Draft',
                        '1' => 'Approved',
                        '2' => 'Paid',
                        default => 'Unknown',
                    })
                    ->color(fn ($state): string => match ($state) {
                        0 => 'warning',
                        1 => 'success',
                        2 => 'info',
                        default => 'primary',
                    }),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->badge()
                    ->getStateUsing(fn ($record) => match($record->keterangan) {
                        'Full Payment' => 'Full Payment',
                        'DP' => 'DP',
                        default => 'Unknown',
                    })
                    ->color(fn ($state): string => match ($state) {
                        'Full Payment' => 'success',
                        'DP' => 'warning',
                        default => 'primary',
                    }),
                TextColumn::make('create_by')->label('Created By')->sortable(),
                TextColumn::make('created_at')->date()->label('Created At')->sortable(),
                TextColumn::make('approval_by')->label('Approval By')->sortable(),
                TextColumn::make('approval_at')->date()->label('Approval At')->sortable(),

            ])

            ->filters([
                //
            ])
            ->actions([
                Action::make('keterangan')
                        ->label('Keterangan')
                        ->icon('heroicon-o-information-circle')
                        ->modalHeading('Ubah Keterangan')
                        ->form([
                            \Filament\Forms\Components\Select::make('keterangan')
                                ->label('Keterangan')
                                ->options([
                                    'full payment' => 'Full Payment',
                                    'dp' => 'DP',
                                ])
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'keterangan' => $data['keterangan'],
                            ]);
                        })
                        ->color('primary'),
                Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check')
                        ->visible(fn ($record) => (int)$record->status === 0) 
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['status' => 1]);

                            activity('invoice-action')
                                ->causedBy(auth()->user())
                                ->withProperties([
                                    'ip'    => request()->ip(),
                                    'menu'  => 'Invoice',
                                    'email' => auth()->user()?->email,
                                    'record_id' => $record->id,
                                    'invoice_number' => $record->invoice_number,
                                    'action' => 'Approve',
                                ])
                                ->tap(function ($activity) {
                                        $activity->email = auth()->user()?->email;
                                        $activity->menu = 'Invoice';
                                    })
                                ->log('Invoice disetujui');

                            Notification::make()
                                ->title('Payroll Approved')
                                ->success()
                                ->send();
                                return $record->fresh();
                        }),
                // Tables\Actions\ViewAction::make()
                //     ->mountUsing(function ($record) {
                //         activity('invoice-action')
                //             ->causedBy(auth()->user())
                //             ->withProperties([
                //                 'ip'              => request()->ip(),
                //                 'menu'            => 'Invoice',
                //                 'email'           => auth()->user()?->email,
                //                 'record_id'       => $record->id,
                //                 'invoice_number'  => $record->invoice_number,
                //                 'action'          => 'View',
                //             ])
                //             ->tap(function ($activity) {
                //                 $activity->email = auth()->user()?->email;
                //                 $activity->menu  = 'Invoice';
                //             })
                //             ->log('Invoice dilihat');
                //     }),
                Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => (int)$record->status === 0) // 👈 hanya tampil jika belum approve
                        ->after(function ($record) {
                            activity('invoice-action')
                                ->causedBy(auth()->user())
                                ->withProperties([
                                    'ip'              => request()->ip(),
                                    'menu'            => 'Invoice',
                                    'email'           => auth()->user()?->email,
                                    'record_id'       => $record->id,
                                    'invoice_number'  => $record->invoice_number,
                                    'action'          => 'Edit',
                                ])
                                ->tap(function ($activity) {
                                    $activity->email = auth()->user()?->email;
                                    $activity->menu  = 'Invoice';
                                })
                                ->log('Invoice diedit');
                        }),

                
                Action::make('download')
                ->label('Download Invoice')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function ($record, DownloadInvoiceService $service) {
                     activity('invoice-action')
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'ip'              => request()->ip(),
                            'menu'            => 'Invoice',
                            'email'           => auth()->user()?->email,
                            'record_id'       => $record->id,
                            'invoice_number'  => $record->invoice_number,
                            'action'          => 'Download',
                        ])
                        ->tap(function ($activity) {
                            $activity->email = auth()->user()?->email;
                            $activity->menu  = 'Invoice';
                        })
                        ->log('Invoice diunduh');
                    return $service->downloadInvoice($record);
                }),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
