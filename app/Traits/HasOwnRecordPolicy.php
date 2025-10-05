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
        
        }else{
            $orgId = \App\Models\Employee::where('email', $user->email)->value('org_id');
            $query->whereHas('employee', function ($q) use ($orgId) {
                $q->where('org_id', $orgId);
            });
        }

        return $query;
    }
    

    public static function canEdit($record): bool
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // // Jika role employee → hanya boleh edit data miliknya sendiri
        if ($user->hasRole('employee')) {
            return $record->employee->email === $user->email;
        }else{
            return auth()->user()->hasAnyRole(['admin', 'manager']);
        }

        // // Jika admin atau manager → hanya boleh edit jika org_id sama
        // $orgId = \App\Models\Employee::where('email', $user->email)->value('org_id');

        // return $record->employee?->org_id === $orgId;
        
        return true;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        if ($user->hasRole('employee')) {
            return $record->employee->email === $user->email;
        }else{
            return auth()->user()->hasAnyRole(['admin', 'manager']);
        }
        return true;
        //   return auth()->user()->hasAnyRole(['admin', 'manager']);
        
       
    }
}
