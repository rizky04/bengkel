<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Label {{ $part->kode }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #f3f4f6; padding: 16px; }
        .toolbar { max-width: 760px; margin: 0 auto 12px; display: flex; gap: 8px; align-items: center; }
        .toolbar a, .toolbar button { padding: 8px 12px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; }
        .print { background: #2563eb; color: #fff; }
        .back { background: #e5e7eb; color: #111; }
        .qty { margin-left: auto; font-size: 13px; color: #374151; }
        .qty input { width: 60px; padding: 6px; border: 1px solid #d1d5db; border-radius: 6px; }
        .sheet { max-width: 760px; margin: 0 auto; background: #fff; padding: 10px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
        .label { border: 1px dashed #cbd5e1; border-radius: 4px; padding: 6px 4px; text-align: center; }
        .label .nama { font-size: 11px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .label .harga { font-size: 12px; font-weight: 700; margin-top: 2px; }
        .label svg { max-width: 100%; height: 40px; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .sheet { max-width: 100%; box-shadow: none; padding: 0; }
            .label { border-color: transparent; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="print" onclick="window.print()">🖨️ Cetak Label</button>
        <a class="back" href="{{ route('parts.index') }}">Kembali</a>
        <form class="qty" method="GET">
            Jumlah label:
            <input type="number" name="qty" min="1" max="60" value="{{ $qty }}" onchange="this.form.submit()">
        </form>
    </div>

    <div class="sheet">
        @for ($i = 0; $i < $qty; $i++)
            <div class="label">
                <div class="nama">{{ $part->nama }}</div>
                <svg class="bc"></svg>
                <div class="harga">{{ rupiah($part->harga_jual) }}</div>
            </div>
        @endfor
    </div>

    <script>
        // Render barcode Code128 dari kode part ke setiap label.
        document.querySelectorAll('.bc').forEach(el => {
            JsBarcode(el, @json($part->kode), { format: 'CODE128', displayValue: true, fontSize: 12, height: 34, margin: 2 });
        });
    </script>
</body>
</html>
