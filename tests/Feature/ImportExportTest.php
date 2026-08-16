<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a@b.test', 'password' => bcrypt('x'), 'role' => 'admin', 'aktif' => true, 'branch_id' => $this->branch()->id,
        ]));
    }

    public function test_export_sparepart_menghasilkan_csv(): void
    {
        $this->makePart(['kode' => 'SP1', 'nama' => 'Oli'], stok: 5);

        $res = $this->get(route('parts.export'));
        $res->assertOk();
        $res->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('kode,nama,kategori', $res->streamedContent());
        $this->assertStringContainsString('SP1,Oli', $res->streamedContent());
    }

    public function test_import_membuat_barang_baru_dengan_stok_awal(): void
    {
        $csv = "kode,nama,kategori,satuan,harga_beli,harga_jual,stok,stok_min,lokasi_rak\n"
             . "SP9,Busi NGK,Busi,pcs,12000,18000,20,3,Rak B\n";
        $file = UploadedFile::fake()->createWithContent('parts.csv', $csv);

        $this->post(route('parts.import'), ['file' => $file])->assertRedirect();

        $part = Part::where('kode', 'SP9')->first();
        $this->assertNotNull($part);
        $this->assertSame('Busi NGK', $part->nama);
        $this->assertSame(20, $part->stok);
        $this->assertSame(18000.0, $part->harga_jual);
        // stok awal tercatat di kartu stok (bukan langsung set)
        $this->assertSame(20, $part->stockMoves()->sum('qty'));
        $this->assertSame('Busi', $part->category->nama);
    }

    public function test_import_memperbarui_barang_tanpa_mengubah_stok(): void
    {
        $part = $this->makePart(['kode' => 'SP1', 'nama' => 'Oli'], stok: 10);

        $csv = "kode,nama,kategori,satuan,harga_beli,harga_jual,stok,stok_min,lokasi_rak\n"
             . "SP1,Oli Mesin,,liter,45000,60000,999,5,\n";
        $file = UploadedFile::fake()->createWithContent('parts.csv', $csv);

        $this->post(route('parts.import'), ['file' => $file]);

        $part->refresh();
        $this->assertSame('Oli Mesin', $part->nama);
        $this->assertSame(60000.0, $part->harga_jual);
        $this->assertSame(5, $part->stok_min);
        $this->assertSame(10, $part->stok); // stok TIDAK diubah lewat import
    }
}
