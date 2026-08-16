<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCat;
use App\Models\Platform;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show()
    {
        return [
            'nama_bengkel' => Setting::get('nama_bengkel', 'Bengkel'),
            'alamat' => Setting::get('alamat', ''),
            'hp' => Setting::get('hp', ''),
            'nota_prefix' => Setting::get('nota_prefix', 'INV'),
            'nota_lebar' => Setting::get('nota_lebar', '58'),
            'pajak_aktif' => Setting::get('pajak_aktif', '0') === '1',
            'pajak_persen' => (float) Setting::get('pajak_persen', '0'),
            'servis_interval_hari' => (int) Setting::get('servis_interval_hari', '90'),
            'platforms' => Platform::orderBy('nama')->get(),
            'expense_cats' => ExpenseCat::orderBy('nama')->get(),
        ];
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'nama_bengkel' => 'required|string|max:255',
            'alamat' => 'nullable|string|max:255',
            'hp' => 'nullable|string|max:30',
            'nota_prefix' => 'required|string|max:10',
            'nota_lebar' => 'required|in:58,80',
            'pajak_aktif' => 'nullable|boolean',
            'pajak_persen' => 'nullable|numeric|min:0|max:100',
            'servis_interval_hari' => 'required|integer|min:1|max:1000',
        ]);
        Setting::put('nama_bengkel', $data['nama_bengkel']);
        Setting::put('alamat', $data['alamat'] ?? '');
        Setting::put('hp', $data['hp'] ?? '');
        Setting::put('nota_prefix', $data['nota_prefix']);
        Setting::put('nota_lebar', $data['nota_lebar']);
        Setting::put('pajak_aktif', $request->boolean('pajak_aktif') ? '1' : '0');
        Setting::put('pajak_persen', (string) ($data['pajak_persen'] ?? 0));
        Setting::put('servis_interval_hari', (string) $data['servis_interval_hari']);

        return $this->show();
    }

    public function storePlatform(Request $request)
    {
        $request->validate(['nama' => 'required|string|max:100']);

        return Platform::firstOrCreate(['nama' => $request->nama]);
    }

    public function togglePlatform(Platform $platform)
    {
        $platform->update(['aktif' => ! $platform->aktif]);

        return $platform;
    }

    public function destroyPlatform(Platform $platform)
    {
        $platform->delete();

        return ['ok' => true];
    }

    public function storeCat(Request $request)
    {
        $request->validate(['nama' => 'required|string|max:100']);

        return ExpenseCat::firstOrCreate(['nama' => $request->nama]);
    }

    public function destroyCat(ExpenseCat $cat)
    {
        $cat->delete();

        return ['ok' => true];
    }
}
