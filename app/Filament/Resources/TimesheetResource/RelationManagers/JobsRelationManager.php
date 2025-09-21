<?php

namespace App\Filament\Resources\TimesheetResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class JobsRelationManager extends RelationManager
{
    protected static string $relationship = 'jobs';

    public function form(Form $form): Form
    {
        return $form->schema([
            Textarea::make('job_description')->required(),
            TextInput::make('job_duration')
                ->numeric()
                ->suffix(' Jam'),
        ]);
    }
}
