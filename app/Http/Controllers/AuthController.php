<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($cred, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Email atau password salah.']);
        }

        if (! $request->user()->aktif) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Akun nonaktif. Hubungi admin.']);
        }

        $request->session()->regenerate();
        \App\Models\ActivityLog::catat('login', $request->user()->name . ' masuk');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
