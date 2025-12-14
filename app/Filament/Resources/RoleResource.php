<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use App\Helpers\FilamentHelper;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
use App\Traits\HasOwnRecordPolicy;
use Spatie\Permission\Traits\HasPermissions;
use App\Traits\HasNavigationPolicy;

class RoleResource extends Resource
{
    use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;
    protected static ?string $model = \App\Models\Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $label = 'Role';
    protected static ?string $pluralLabel = 'Roles';

    public static function form(Form $form): Form
    { 
        $resources = FilamentHelper::getResources();
        $sections = [];

        foreach ($resources as $key => $label) {
            $sections[] = Section::make($label)
                ->schema([
                    CheckboxList::make("permissions_{$key}")
                        ->options([
                            "{$key}.read" => 'Read',
                            "{$key}.create" => 'Create',
                            "{$key}.update" => 'Update',
                            "{$key}.delete" => 'Delete',
                        ])
                        ->columns(2)
                        ->bulkToggleable()
                        ->afterStateHydrated(function (CheckboxList $component, $state, $record) use ($key) {
                            if (! $record) return;

                            $permissions = $record->permissions
                                ->pluck('name')
                                ->filter(fn ($name) => str_starts_with($name, "{$key}."))
                                ->values()
                                ->toArray();

                            $component->state($permissions);
                        })
                        ->label('Permissions'),
                ])
                ->collapsible()
                
                ->collapsed();
        }

        return $form
            ->schema(array_merge([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Role'),
            ], $sections));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Total Permissions')
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->permissions_count ?? $record->permissions()->count()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withCount('permissions');
    }
}
