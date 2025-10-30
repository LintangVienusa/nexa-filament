<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LogActivityResource\Pages;
use App\Filament\Resources\LogActivityResource\RelationManagers;
use App\Models\LogActivity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Spatie\Activitylog\Models\Activity;
use App\Traits\HasOwnRecordPolicy;
use Spatie\Permission\Traits\HasPermissions;
use App\Traits\HasNavigationPolicy;


class LogActivityResource extends Resource
{
    use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;
    protected static ?string $model = LogActivity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Log Activity';
    protected static ?string $pluralModelLabel = 'Log Activities';
    protected static ?string $slug = 'log-activities';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                    Tables\Columns\TextColumn::make('email')
                        ->label('User')
                        ->sortable()
                        ->searchable(),
                    Tables\Columns\TextColumn::make('log_name')
                        ->sortable()
                        ->searchable(),
                    Tables\Columns\TextColumn::make('menu')
                        ->sortable()
                        ->searchable(),
                    Tables\Columns\TextColumn::make('description')
                        ->limit(50)
                        ->wrap(),
                    Tables\Columns\TextColumn::make('event')
                        ->sortable()
                        ->badge()
                        ->colors([
                            'success' => 'created',
                            'warning' => 'updated',
                            'danger' => 'deleted',
                        ]),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Created')
                        ->dateTime('d M Y H:i')
                        ->sortable(),
                ])
                ->actions([
                        ViewAction::make()
                            ->modalHeading('Detail Log Activity')
                            ->modalContent(fn ($record) => view('filament.log-activity-detail', ['record' => $record])),
                    ])
                ->defaultSort('created_at', 'desc')
            
            ->filters([
                SelectFilter::make('email')
                    ->label('Filter Email')
                    ->options(function () {
                        return Activity::query()
                            ->whereNotNull('email')
                            ->distinct()
                            ->pluck('email', 'email')
                            ->toArray();
                    }),

                // 🔽 Filter Menu
                SelectFilter::make('menu')
                    ->label('Filter Menu')
                    ->options(function () {
                        return Activity::query()
                            ->whereNotNull('menu')
                            ->distinct()
                            ->pluck('menu', 'menu')
                            ->toArray();
                    }),
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

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogActivities::route('/'),
            // 'create' => Pages\CreateLogActivity::route('/create'),
            // 'edit' => Pages\EditLogActivity::route('/{record}/edit'),
        ];
    }
}
