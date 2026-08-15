<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCat;
use App\Models\Platform;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('settings.edit', [
            'platforms' => Platform::orderBy('nama')->get(),
            'cats' => ExpenseCat::orderBy('nama')->get(),
        ]);
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
        ]);

        Setting::put('nama_bengkel', $data['nama_bengkel']);
        Setting::put('alamat', $data['alamat'] ?? '');
        Setting::put('hp', $data['hp'] ?? '');
        Setting::put('nota_prefix', $data['nota_prefix']);
        Setting::put('nota_lebar', $data['nota_lebar']);
        Setting::put('pajak_aktif', $request->boolean('pajak_aktif') ? '1' : '0');
        Setting::put('pajak_persen', (string) ($data['pajak_persen'] ?? 0));

        return back()->with('success', 'Pengaturan disimpan.');
    }

    public function storePlatform(Request $request)
    {
        $request->validate(['nama' => 'required|string|max:100']);
        Platform::firstOrCreate(['nama' => $request->nama]);

        return back()->with('success', 'Platform ditambahkan.');
    }

    public function togglePlatform(Platform $platform)
    {
        $platform->update(['aktif' => ! $platform->aktif]);

        return back()->with('success', 'Status platform diperbarui.');
    }

    public function destroyPlatform(Platform $platform)
    {
        $platform->delete();

        return back()->with('success', 'Platform dihapus.');
    }

    public function storeCat(Request $request)
    {
        $request->validate(['nama' => 'required|string|max:100']);
        ExpenseCat::firstOrCreate(['nama' => $request->nama]);

        return back()->with('success', 'Kategori pengeluaran ditambahkan.');
    }

    public function destroyCat(ExpenseCat $cat)
    {
        $cat->delete();

        return back()->with('success', 'Kategori dihapus.');
    }
}
