<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

        $token = $user->createToken('mobile')->plainTextToken;

        $employee = $user->employee()->with('organization')->first();

        // $filePath = $employee->file_photo;
        $filePath = storage_path('app/public/' . $employee->file_photo);
        if (file_exists($filePath)) {
            $fileData = file_get_contents($filePath);
            $base64 = base64_encode($fileData);
        } else {
             $base64="a";
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
                'division'     => optional(optional($employee)->organization)->divisi_name ?? null,
                'unit_name'    => optional(optional($employee)->organization)->unit_name ?? null,
                'job_title'    => $employee->job_title ?? null,
                // 'file_photoa'    => $filePath ?? null,
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
