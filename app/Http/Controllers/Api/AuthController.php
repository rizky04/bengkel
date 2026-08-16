<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Acl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $cred = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device' => 'nullable|string',
        ]);

        if (! Auth::attempt(['email' => $cred['email'], 'password' => $cred['password']])) {
            throw ValidationException::withMessages(['email' => 'Email atau password salah.']);
        }
        $user = Auth::user();
        if (! $user->aktif) {
            throw ValidationException::withMessages(['email' => 'Akun nonaktif.']);
        }

        $token = $user->createToken($cred['device'] ?? 'mobile')->plainTextToken;

        return ['token' => $token, 'user' => static::payload($user)];
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return ['ok' => true];
    }

    public function me(Request $request)
    {
        return ['user' => static::payload($request->user())];
    }

    /**
     * Payload user + daftar izin menu. Mobile memakai `permissions` untuk
     * menyembunyikan menu yang tak boleh diakses — mirror `menu_access` roti.
     */
    public static function payload($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'branch_id' => $user->branch_id,
            'is_admin' => $user->isAdmin(),
            'permissions' => $user->isAdmin() ? Acl::all() : ($user->roleModel()?->permissionKeys() ?? []),
        ];
    }
}
