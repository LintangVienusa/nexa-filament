<?php

namespace app\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasOwnRecordPolicy
{
    protected static string $ownerColumn = 'email';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole('employee')) {
            $query->where(static::$ownerColumn, auth()->user()->email);
        }

        return $query;
    }

    public static function canEdit($record): bool
    {
        if (auth()->user()->hasRole('employee')) {
            return $record->{static::$ownerColumn} === auth()->user()->email;
        }

        return true;
    }

    public static function canDelete($record): bool
    {
        if (auth()->user()->hasRole('employee')) {
            return $record->{static::$ownerColumn} === auth()->user()->email;
        }

        return true;
    }
}
