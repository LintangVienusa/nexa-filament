<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

trait HasNavigationPolicy
{
    protected static function hasReadPermission(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $resourceName = class_basename(static::class);

       $permission = Str::of($resourceName)
            ->replaceLast('Resource', '')
            ->snake()
            ->lower()
            ->append('.read')
            ->toString();
// dd(
//         static::class,
//         $permission,
//         $user->can($permission),
//         $user->getAllPermissions()->pluck('name')->toArray()
//     );
        return $user->can($permission);
    }

    public static function shouldRegisterNavigation(): bool
    {
        
        $user = Auth::user();
        $hasRole = $user->setConnection('mysql')->hasRole('employee');
        $jobTitle = $user->employee?->job_title;
        $tipeEmployee = $user->employee?->employee_type;
        $unitName = strtoupper($user->employee?->organization?->unit_name ?? '');
        
        // \Log::info('shouldRegisterNavigation called for '.static::class, [
        //         'user_email' => $user->email ?? 'unknown',
        //         'job_title' => $jobTitle,
        //         'unit_name' => $unitName,
        //         'roles' => $user->roles->pluck('name')->toArray(),
        //     ]);
        if (!$user) {
            return false;
        }

       

        if ($user->hasAnyRole(['superadmin','admin'])) {
            if ($tipeEmployee === 'mitra') {
                $resourceClass = static::class;
                $allowedResources = [
                        \App\Filament\Resources\HomeConnectReportResource::class,
                    ];
                    
                    return in_array($resourceClass, $allowedResources);
            }else{
                return true;
            }
            

        }

        // if (in_array($jobTitle, ['CEO','CTO'])) {
        //    if ($tipeEmployee === 'mitra') {
        //         $resourceClass = static::class;
        //         $allowedResources = [
        //                 \App\Filament\Resources\HomeConnectReportResource::class,
        //             ];
                    
        //             return in_array($resourceClass, $allowedResources);
        //     }else{
        //         return true;
        //     }
        // }

        // if ($tipeEmployee === 'mitra') {
        //     $resourceClass = static::class;
        //     $allowedResources = [
        //             \App\Filament\Resources\HomeConnectReportResource::class,
        //         ];
                
        //         return in_array($resourceClass, $allowedResources);
        // }

        // if (in_array($jobTitle, ['VP','Manager','SPV'])) {
        //     // return $unitName === 'IT' || $unitName === 'HR'; 
        //      if ($unitName != 'WAREHOUSE') {
        //         $resourceClass = static::class;

        //         $allowedResources = [
        //             \App\Filament\Resources\EmployeeResource::class,
        //             \App\Filament\Resources\AttendanceResource::class,
        //             \App\Filament\Resources\OvertimeResource::class,
        //             \App\Filament\Resources\TimesheetResource::class,
        //             \App\Filament\Resources\PayrollResource::class,
        //             \App\Filament\Resources\LeaveResource::class,
        //             \App\Filament\Resources\ProfileResource::class,
        //             \App\Filament\Resources\UserResource::class,
        //             // \App\Filament\Resources\MappingRegionResource::class,
        //         ];

        //         return in_array($resourceClass, $allowedResources);
        //     }
        // }

        

        // if ($unitName === 'WAREHOUSE') {
        //     $resourceClass = static::class;

        //     $allowedResources = [
        //         \App\Filament\Resources\EmployeeResource::class,
        //         \App\Filament\Resources\AttendanceResource::class,
        //         \App\Filament\Resources\OvertimeResource::class,
        //         \App\Filament\Resources\TimesheetResource::class,
        //         \App\Filament\Resources\PayrollResource::class,
        //         \App\Filament\Resources\AssetMovementResource::class,
        //         \App\Filament\Resources\AssetResource::class,
        //         \App\Filament\Resources\AssetTransactionResource::class,
        //         \App\Filament\Resources\InventoryAssetResource::class,
        //             \App\Filament\Resources\ProfileResource::class,
        //             \App\Filament\Resources\LeaveResource::class,
        //             \App\Filament\Resources\UserResource::class,
        //             // \App\Filament\Resources\MappingRegionResource::class,
        //     ];

        //     return in_array($resourceClass, $allowedResources);
        // }

        if ( $user->hasRole('employee')) {
            if ($tipeEmployee === 'mitra') {
                $resourceClass = static::class;
                $allowedResources = [
                        \App\Filament\Resources\HomeConnectReportResource::class,
                    ];
                    
                    return in_array($resourceClass, $allowedResources);
            }

            if ($unitName === 'TECHNICIAN') {
                $resourceClass = static::class;

                $allowedResources = [
                    \App\Filament\Resources\EmployeeResource::class,
                    \App\Filament\Resources\AttendanceResource::class,
                    \App\Filament\Resources\OvertimeResource::class,
                    \App\Filament\Resources\TimesheetResource::class,
                    \App\Filament\Resources\PayrollResource::class,
                    \App\Filament\Resources\LeaveResource::class,
                    \App\Filament\Resources\ProfileResource::class,
                    \App\Filament\Resources\UserResource::class,
                    // \App\Filament\Resources\MappingRegionResource::class,
                ];

                return in_array($resourceClass, $allowedResources);
            }

            if ($unitName != 'WAREHOUSE') {
                $resourceClass = static::class;

                $allowedResources = [
                    \App\Filament\Resources\EmployeeResource::class,
                    \App\Filament\Resources\AttendanceResource::class,
                    \App\Filament\Resources\OvertimeResource::class,
                    \App\Filament\Resources\TimesheetResource::class,
                    \App\Filament\Resources\PayrollResource::class,
                    \App\Filament\Resources\LeaveResource::class,
                    \App\Filament\Resources\ProfileResource::class,
                    \App\Filament\Resources\UserResource::class,
                    // \App\Filament\Resources\MappingRegionResource::class,
                ];

                return in_array($resourceClass, $allowedResources);
            }

            
        }

         if (
            ! static::hasReadPermission() &&
            ! $user->hasAnyRole(['superadmin', 'admin'])
        ) {
            return false;
        }

        return static::hasReadPermission();
    }

    
}
