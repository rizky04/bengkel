<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Platform;
use App\Models\Promo;
use App\Models\Setting;
use App\Models\User;

/**
 * Data pendukung POS yang boleh diakses siapa pun yang sudah login:
 * platform, mekanik, promo publik, dan konfigurasi pajak.
 */
class MetaController extends Controller
{
    public function platforms()
    {
        return Platform::where('aktif', true)->orderBy('nama')->get();
    }

    public function mekaniks()
    {
        return User::whereIn('role', ['mekanik', 'admin'])->where('aktif', true)
            ->orderBy('name')->get(['id', 'name', 'role']);
    }

    public function promos()
    {
        return Promo::berlaku()
            ->where(fn ($q) => $q->whereNull('kode')->orWhere('kode', ''))
            ->orderBy('nama')->get();
    }

    public function tax()
    {
        return [
            'aktif' => Setting::get('pajak_aktif', '0') === '1',
            'persen' => (float) Setting::get('pajak_persen', '0'),
        ];
    }
}
