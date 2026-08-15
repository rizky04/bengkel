<?php

namespace App\Models;

use App\Exceptions\StockException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Part extends Model
{
    protected $guarded = [];

    // rupiah tak berdesimal: hindari tampil "45000,00" di input number
    protected $casts = ['harga_beli' => 'float', 'harga_jual' => 'float'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMoves()
    {
        return $this->hasMany(StockMove::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Stok cabang aktif. Bila query pakai scopeWithStok(), nilai sudah ada di atribut;
     * jika tidak, ambil dari inventories cabang aktif (1 query per part).
     */
    public function getStokAttribute(): int
    {
        if (array_key_exists('stok', $this->attributes)) {
            return (int) $this->attributes['stok'];
        }

        return (int) ($this->inventories()->where('branch_id', current_branch())->value('stok') ?? 0);
    }

    /** Stok di cabang tertentu. */
    public function stokDi(?int $branchId): int
    {
        return (int) ($this->inventories()->where('branch_id', $branchId)->value('stok') ?? 0);
    }

    /** Sertakan kolom `stok` = stok cabang $branchId (default cabang aktif) tanpa N+1. */
    public function scopeWithStok($q, ?int $branchId = null)
    {
        $branchId = $branchId ?? current_branch();

        return $q->addSelect('parts.*')
            ->selectSub(
                Inventory::selectRaw('COALESCE(stok,0)')
                    ->whereColumn('inventories.part_id', 'parts.id')
                    ->where('inventories.branch_id', $branchId)
                    ->limit(1),
                'stok'
            );
    }

    /** Stok cabang aktif di bawah minimum. Wajar dipakai bareng withStok(). */
    public function scopeLowStock($q, ?int $branchId = null)
    {
        $branchId = $branchId ?? current_branch();

        return $q->whereRaw(
            'COALESCE((select stok from inventories where inventories.part_id = parts.id and inventories.branch_id = ?), 0) <= parts.stok_min',
            [$branchId]
        );
    }

    /**
     * Satu-satunya jalur perubahan stok (pembelian, opname, POS, retur), PER CABANG.
     * Kunci baris inventory (cabang+part) agar dua transaksi bersamaan tak menimbulkan selisih.
     *
     * @param  string  $tipe  in | out | adjust  (adjust: $qty adalah selisih, boleh negatif)
     */
    public function moveStock(int $branchId, string $tipe, int $qty, array $ref = []): StockMove
    {
        return DB::transaction(function () use ($branchId, $tipe, $qty, $ref) {
            $inv = Inventory::where('branch_id', $branchId)->where('part_id', $this->id)
                ->lockForUpdate()->first()
                ?? Inventory::create(['branch_id' => $branchId, 'part_id' => $this->id, 'stok' => 0]);

            $delta = match ($tipe) {
                'in' => abs($qty),
                'out' => -abs($qty),
                'adjust' => $qty,
                default => throw new InvalidArgumentException("Tipe mutasi stok tidak dikenal: $tipe"),
            };

            $saldo = $inv->stok + $delta;
            if ($saldo < 0) {
                throw new StockException("Stok {$this->nama} tidak cukup (tersedia {$inv->stok}, diminta " . abs($delta) . ').');
            }

            $inv->update(['stok' => $saldo]);

            return StockMove::create([
                'branch_id' => $branchId,
                'part_id' => $this->id,
                'tipe' => $tipe,
                'qty' => $delta,
                'saldo' => $saldo,
                'ref_tipe' => $ref['tipe'] ?? null,
                'ref_id' => $ref['id'] ?? null,
                'keterangan' => $ref['keterangan'] ?? null,
                'user_id' => auth()->id(),
                'tgl' => now(),
            ]);
        });
    }
}
