<?php

namespace Tests;

use App\Models\Branch;
use App\Models\Part;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected ?Branch $branch = null;

    /** Cabang default untuk test (multi-cabang). */
    protected function branch(): Branch
    {
        return $this->branch ??= Branch::create(['nama' => 'Pusat', 'aktif' => true]);
    }

    /** Buat part + set stok awal di cabang test (stok kini per cabang di inventories). */
    protected function makePart(array $attrs = [], int $stok = 0): Part
    {
        $part = Part::create(array_merge([
            'kode' => 'P' . substr(md5(uniqid()), 0, 6),
            'nama' => 'Barang', 'satuan' => 'pcs',
            'harga_beli' => 40000, 'harga_jual' => 55000, 'stok_min' => 2,
        ], $attrs));

        if ($stok !== 0) {
            $part->inventories()->create(['branch_id' => $this->branch()->id, 'stok' => $stok]);
        }

        return $part;
    }
}
