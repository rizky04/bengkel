<?php

namespace App\Support;

/**
 * Katalog izin (permission) terpusat — satu izin per menu/kemampuan.
 * Dipakai oleh: navigasi sidebar, middleware `permission`, dan halaman Role & Akses.
 * Tambah menu baru? Daftarkan di sini agar otomatis muncul di pengaturan role.
 */
class Acl
{
    /** [Grup => [key => Label]] */
    public static function groups(): array
    {
        return [
            'Utama' => [
                'dashboard' => 'Dashboard',
                'pos' => 'Kasir / POS',
                'transactions' => 'Transaksi',
                'returns' => 'Retur',
                'reminders' => 'Pengingat Servis',
                'shifts' => 'Shift Kasir',
            ],
            'Master Data' => [
                'customers' => 'Pelanggan',
                'vehicles' => 'Kendaraan',
                'parts' => 'Sparepart',
                'services' => 'Jasa / Servis',
                'categories' => 'Kategori',
                'suppliers' => 'Supplier',
            ],
            'Inventori' => [
                'purchases' => 'Pembelian',
                'stock' => 'Stok & Opname',
                'stock_transfer' => 'Transfer Stok',
            ],
            // Hak admin/owner: ubah transaksi yang sudah lunas & pembatalan.
            // (Transaksi yang masih terbuka tetap bisa diedit kasir tanpa izin ini.)
            'Hak Khusus' => [
                'transactions_edit' => 'Edit Transaksi Lunas & Pembatalan',
                'purchases_edit' => 'Edit & Batal Pembelian',
                'approvals' => 'Setujui Pengajuan Perubahan',
            ],
            'Keuangan & Admin' => [
                'promos' => 'Promo',
                'expenses' => 'Pengeluaran',
                'employees' => 'Karyawan & Gaji',
                'reports' => 'Laporan',
                'branches' => 'Cabang',
                'users' => 'Pengguna',
                'roles' => 'Role & Akses',
                'activity' => 'Log Aktivitas',
                'settings' => 'Pengaturan',
            ],
        ];
    }

    /** Semua key izin (flat). */
    public static function all(): array
    {
        return collect(static::groups())->flatMap(fn ($items) => array_keys($items))->values()->all();
    }

    /** Preset izin default per role bawaan. */
    public static function preset(string $roleKey): array
    {
        return match ($roleKey) {
            'kasir' => [
                'dashboard', 'pos', 'transactions', 'returns', 'reminders', 'shifts',
                'customers', 'vehicles', 'parts', 'services', 'categories', 'suppliers',
                'purchases', 'stock',
            ],
            'mekanik' => ['dashboard', 'transactions', 'reminders'],
            default => [], // admin = full access (is_admin), tak perlu daftar
        };
    }
}
