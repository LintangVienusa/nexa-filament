<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;


trait HasOwnRecordPolicy
{
    protected static string $ownerColumn = 'email';

    // public static function getEloquentQuery(): Builder
    // {
    //     $query = parent::getEloquentQuery();
    //     $user = Auth::user();
    //     $model = new static::$model;
    //     $table = $model->getTable();

        
    //     $jobTitle = $user->employee?->job_title;

    //     if (($user->hasRole('superadmin') || $user->hasRole('admin') )) {
    //         return $query;
    //     }

    //     if ($model instanceof \App\Models\Employee) {
    //         return $query;
    //     }

    //     if ($user->hasRole('employee')) {

    //         if (property_exists(static::class, 'ownerColumn')
    //             && Schema::connection($model->getConnectionName())->hasColumn($table, static::$ownerColumn))
    //         {
    //             $query->where(static::$ownerColumn, $user->email);
    //         }
    //         elseif (method_exists($model, 'employee')) {
    //             $query->whereHas('employee', function ($q) use ($user) {
    //                 $q->where('email', $user->email);
    //             });
    //         }

    //     } else {
    //         $orgId = \App\Models\Employee::where('email', $user->email)->value('org_id');

    //         if (method_exists($model, 'employee')) {
    //             $query->whereHas('employee', function ($q) use ($orgId) {
    //                 $q->where('org_id', $orgId);
    //             });
    //         }
    //     }

    //     return $query;
    // }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user()->setConnection('mysql'); // pastikan roles query aman
        
        // $hasRole = $user->setConnection('mysql')->hasRole('employee');
        $hasRole = $user->hasRole('employee');

        $query = parent::getEloquentQuery();
        $model = new static::$model;
        $table = $model->getTable();
        $jobTitle = $user->employee?->job_title;
        $orgId = $user->employee?->org_id;
        $orgunit_name = $user->employee?->organization?->unit_name;

        // Superadmin, admin, CEO, CTO bisa lihat semua
        if ($user->hasRole(['superadmin', 'admin']) && in_array($jobTitle, ['CEO','CTO'])) {
            return $query;
        }

        if($orgunit_name==='HR'){
            return $query;
        }else{
            // VP, Manager, SPV hanya lihat sesama org_id
            if (in_array($jobTitle, ['VP','Manager','SPV']) && $orgId && method_exists($model, 'employee')) {
                return $query->whereHas('employee', fn($q) => $q->where('org_id', $orgId));
            }
        
            

            // Employee & Staff hanya lihat record sendiri
            if ($user->hasRole('employee') || $jobTitle === 'Staff') {
                if (property_exists(static::class, 'ownerColumn')
                    && Schema::connection($model->getConnectionName())->hasColumn($table, static::$ownerColumn)) {
                    return $query->where(static::$ownerColumn, $user->email);
                } elseif (method_exists($model, 'employee')) {
                    return $query->whereHas('employee', fn($q) => $q->where('email', $user->email));
                }
            }

            return $query;
        }
    }

    // public static function canEdit($record): bool
    // {
    //     $user = auth()->user();

    //     if ($record instanceof \App\Models\Employee) {
    //         return $user->hasAnyRole(['superadmin','admin', 'manager']);
    //     }

    //     if ($user->hasRole('employee')) {
    //         return $record->employee?->email === $user->email;
    //     }

    //     return $user->hasAnyRole(['superadmin','admin', 'manager']);
    // }

    public static function canDelete($record): bool
    {
        $user = auth()->user()->setConnection('mysql');

        if ($record instanceof \App\Models\Employee) {
            return $user->hasAnyRole(['superadmin','admin', 'manager']);
        }

        return $user->hasAnyRole(['superadmin','admin', 'manager']);
    }
}
