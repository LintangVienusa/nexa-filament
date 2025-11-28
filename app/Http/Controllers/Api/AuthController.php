<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->tokens()->delete();
        \DB::table('sessions')->where('user_id', $user->id)->delete();

        $sessionToken = Str::random(60);
        $user->forceFill(['current_session_token' => $sessionToken])->save();

        $token = $user->createToken('mobile')->plainTextToken;

        $employee = $user->employee()->with('organization')->first();
        $file =$employee->file_photo;
        if($file != '' ){
            $filePath = storage_path('app/public/' . $employee->file_photo);
            $base64 = file_exists($filePath)
            ? base64_encode(file_get_contents($filePath))
            : null;
        }else{
            $base64 = null;
        }
        

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user_id'      => $user->id,
                'username'     => $user->name,
                'email'        => $user->email,
                'employee_id'  => $employee->employee_id ?? null,
                'full_name'    => $employee->full_name ?? null,
                'employee_type'=> $employee->employee_type ?? null,
                'division'     => optional(optional($employee)->organization)->divisi_name ?? null,
                'unit_name'    => optional(optional($employee)->organization)->unit_name ?? null,
                'job_title'    => $employee->job_title ?? null,
                'file_photo'    => $base64 ?? null,
                'token'        => $token,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ]);
    }
}
