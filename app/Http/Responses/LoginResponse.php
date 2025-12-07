<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        $token = Str::random(60);
        $user->forceFill(['current_session_token' => $token])->save();

        Session::put('current_session_token', $token);

        $user->tokens()->delete();

        return redirect()->intended('/admin');
    }
}
