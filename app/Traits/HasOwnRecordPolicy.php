<?php

namespace app\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait HasOwnRecordPolicy
{
    protected static string $ownerColumn = 'email';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if ($user->hasRole('employee')) {

            
            $model = new static::$model;
            $table = $model->getTable();

            if (property_exists(static::class, 'ownerColumn') 
                && Schema::connection($model->getConnectionName())->hasColumn($table, static::$ownerColumn)) 
            {
                $query->where(static::$ownerColumn, $user->email);
            }
            elseif (method_exists($model, 'employee')) {
                $query->whereHas('employee', function ($q) use ($user) {
                    $q->where('email', $user->email);
                });
            }
        
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
        // if (auth()->user()->hasRole('Employee')) {
        //     return $record->{static::$ownerColumn} === auth()->user()->email;
        // }

        // return true;
        
        return auth()->user()->hasAnyRole(['admin', 'manager']);
    }
}
