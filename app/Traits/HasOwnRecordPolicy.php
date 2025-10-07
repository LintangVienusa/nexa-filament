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
        $model = new static::$model;
        $table = $model->getTable();

        if ($model instanceof \App\Models\Employee) {
            return $query;
        }

        if ($user->hasRole('employee')) {

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

        } else {
            $orgId = \App\Models\Employee::where('email', $user->email)->value('org_id');

            if (method_exists($model, 'employee')) {
                $query->whereHas('employee', function ($q) use ($orgId) {
                    $q->where('org_id', $orgId);
                });
            }
        }

        return $query;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if ($record instanceof \App\Models\Employee) {
            return $user->hasAnyRole(['admin', 'manager']);
        }

        if ($user->hasRole('employee')) {
            return $record->employee?->email === $user->email;
        }

        return $user->hasAnyRole(['admin', 'manager']);
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        if ($record instanceof \App\Models\Employee) {
            return $user->hasAnyRole(['admin', 'manager']);
        }

        if ($user->hasRole('employee')) {
            return $record->employee?->email === $user->email;
        }

        return $user->hasAnyRole(['admin', 'manager']);
    }
}
