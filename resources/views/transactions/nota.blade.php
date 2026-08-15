<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nota {{ $trx->no_nota }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; color: #000; background: #f3f4f6; padding: 16px; }
        .nota { width: 300px; margin: 0 auto; background: #fff; padding: 16px; }
        .c { text-align: center; }
        .r { text-align: right; }
        .b { font-weight: bold; }
        hr { border: none; border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 1px 0; }
        .row { display: flex; justify-content: space-between; }
        .toolbar { width: 300px; margin: 0 auto 12px; display: flex; gap: 8px; }
        .toolbar button, .toolbar a { flex: 1; padding: 8px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; text-align: center; text-decoration: none; }
        .print { background: #2563eb; color: #fff; }
        .bt { background: #0ea5e9; color: #fff; }
        .back { background: #e5e7eb; color: #111; }
        @media print { body { background: #fff; padding: 0; } .toolbar { display: none; } .nota { width: 100%; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="print" onclick="window.print()">🖨️ Cetak</button>
        <button class="bt" onclick="cetakBluetooth()">📶 Bluetooth</button>
        <a class="back" href="{{ route('transactions.show', $trx) }}">Kembali</a>
    </div>

    <div class="nota">
        <div class="c b">{{ \App\Models\Setting::get('nama_bengkel', 'Bengkel') }}</div>
        <div class="c">{{ \App\Models\Setting::get('alamat', '') }}</div>
        <div class="c">{{ \App\Models\Setting::get('hp', '') }}</div>
        <hr>
        <div class="row"><span>No</span><span>{{ $trx->no_nota }}</span></div>
        <div class="row"><span>Tgl</span><span>{{ $trx->tgl?->format('d/m/Y H:i') }}</span></div>
        <div class="row"><span>Kasir</span><span>{{ $trx->kasir?->name }}</span></div>
        @if ($trx->customer)<div class="row"><span>Plgn</span><span>{{ $trx->customer->nama }}</span></div>@endif
        @if ($trx->vehicle)<div class="row"><span>Kend</span><span>{{ $trx->vehicle->plat }}</span></div>@endif
        <hr>
        <table>
            @foreach ($trx->items as $it)
                <tr><td colspan="2">{{ $it->nama }}</td></tr>
                <tr>
                    <td>{{ $it->qty }} x {{ number_format($it->harga, 0, ',', '.') }}@if($it->diskon > 0) -{{ number_format($it->diskon, 0, ',', '.') }}@endif</td>
                    <td class="r">{{ number_format($it->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>
        <hr>
        <div class="row"><span>Subtotal</span><span>{{ number_format($trx->subtotal, 0, ',', '.') }}</span></div>
        @if ($trx->diskon > 0)<div class="row"><span>Diskon</span><span>-{{ number_format($trx->diskon, 0, ',', '.') }}</span></div>@endif
        @if ($trx->pajak > 0)<div class="row"><span>Pajak</span><span>{{ number_format($trx->pajak, 0, ',', '.') }}</span></div>@endif
        <div class="row b"><span>TOTAL</span><span>{{ number_format($trx->total, 0, ',', '.') }}</span></div>
        <div class="row"><span>Bayar</span><span>{{ number_format($trx->dibayar, 0, ',', '.') }}</span></div>
        @if ($trx->dibayar >= $trx->total)
            <div class="row"><span>Kembali</span><span>{{ number_format($trx->dibayar - $trx->total, 0, ',', '.') }}</span></div>
        @else
            <div class="row"><span>Sisa</span><span>{{ number_format($trx->total - $trx->dibayar, 0, ',', '.') }}</span></div>
        @endif
        <hr>
        <div class="c">** {{ strtoupper($trx->status) }} **</div>
        <div class="c" style="margin-top:8px;">Terima kasih 🙏</div>
    </div>

    @php
        $lebar = (int) \App\Models\Setting::get('nota_lebar', '58');
        $chars = $lebar >= 80 ? 42 : 32; // karakter per baris Font A
        $nota = [
            'header' => array_filter([
                \App\Models\Setting::get('nama_bengkel', 'Bengkel'),
                \App\Models\Setting::get('alamat', ''),
                \App\Models\Setting::get('hp', ''),
            ]),
            'meta' => array_filter([
                ['No', $trx->no_nota],
                ['Tgl', $trx->tgl?->format('d/m/Y H:i')],
                ['Kasir', $trx->kasir?->name],
                $trx->customer ? ['Plgn', $trx->customer->nama] : null,
                $trx->vehicle ? ['Kend', $trx->vehicle->plat] : null,
            ]),
            'items' => $trx->items->map(fn ($it) => [
                'nama' => $it->nama,
                'ket' => $it->qty . ' x ' . number_format($it->harga, 0, ',', '.') . ($it->diskon > 0 ? ' -' . number_format($it->diskon, 0, ',', '.') : ''),
                'sub' => number_format($it->subtotal, 0, ',', '.'),
            ]),
            'totals' => array_values(array_filter([
                ['Subtotal', number_format($trx->subtotal, 0, ',', '.')],
                $trx->diskon > 0 ? ['Diskon', '-' . number_format($trx->diskon, 0, ',', '.')] : null,
                $trx->pajak > 0 ? ['Pajak', number_format($trx->pajak, 0, ',', '.')] : null,
            ])),
            'total' => number_format($trx->total, 0, ',', '.'),
            'bayar' => number_format($trx->dibayar, 0, ',', '.'),
            'kembali' => $trx->dibayar >= $trx->total
                ? ['Kembali', number_format($trx->dibayar - $trx->total, 0, ',', '.')]
                : ['Sisa', number_format($trx->total - $trx->dibayar, 0, ',', '.')],
            'status' => strtoupper($trx->status),
        ];
    @endphp

    <script>
        const NOTA = @json($nota);
        const W = {{ $chars }};

        // ── Format teks struk ──
        const pad = (l, r) => { l = String(l); r = String(r); const s = W - l.length - r.length; return l + (s > 0 ? ' '.repeat(s) : ' ') + r; };
        const mid = (s) => { s = String(s); const p = Math.max(0, Math.floor((W - s.length) / 2)); return ' '.repeat(p) + s; };
        const garis = () => '-'.repeat(W);

        function baris() {
            const L = [];
            NOTA.header.forEach(h => L.push({ t: mid(h), b: false }));
            L.push({ t: garis() });
            NOTA.meta.forEach(m => L.push({ t: pad(m[0], m[1]) }));
            L.push({ t: garis() });
            NOTA.items.forEach(it => { L.push({ t: it.nama }); L.push({ t: pad(it.ket, it.sub) }); });
            L.push({ t: garis() });
            NOTA.totals.forEach(x => L.push({ t: pad(x[0], x[1]) }));
            L.push({ t: pad('TOTAL', NOTA.total), b: true });
            L.push({ t: pad('Bayar', NOTA.bayar) });
            L.push({ t: pad(NOTA.kembali[0], NOTA.kembali[1]) });
            L.push({ t: garis() });
            L.push({ t: mid('** ' + NOTA.status + ' **') });
            L.push({ t: mid('Terima kasih') });
            return L;
        }

        // ── ESC/POS bytes ──
        function escpos() {
            const bytes = [0x1B, 0x40]; // init
            const enc = (s) => { for (const ch of s) bytes.push(ch.charCodeAt(0) & 0xFF); };
            for (const ln of baris()) {
                bytes.push(0x1B, 0x45, ln.b ? 1 : 0); // bold on/off
                enc(ln.t);
                bytes.push(0x0A);
            }
            bytes.push(0x1B, 0x45, 0);
            bytes.push(0x0A, 0x0A, 0x0A);   // feed
            bytes.push(0x1D, 0x56, 0x00);   // potong (diabaikan printer tanpa cutter)
            return new Uint8Array(bytes);
        }

        // ponytail: UUID Serial-Port-Service ini dipakai mayoritas printer thermal 58mm murah;
        // sebagian merek beda — kalau gagal connect, ganti SERVICE/CHAR sesuai printer.
        const SERVICE = '000018f0-0000-1000-8000-00805f9b34fb';
        const CHAR = '00002af1-0000-1000-8000-00805f9b34fb';

        async function cetakBluetooth() {
            if (!navigator.bluetooth) {
                alert('Perangkat/browser ini tidak mendukung Web Bluetooth. Pakai Chrome di Android, atau tombol Cetak biasa.');
                return;
            }
            try {
                const device = await navigator.bluetooth.requestDevice({ acceptAllDevices: true, optionalServices: [SERVICE] });
                const server = await device.gatt.connect();
                const service = await server.getPrimaryService(SERVICE);
                const ch = await service.getCharacteristic(CHAR);

                const data = escpos();
                const CHUNK = 180;
                for (let i = 0; i < data.length; i += CHUNK) {
                    const part = data.slice(i, i + CHUNK);
                    if (ch.writeValueWithoutResponse) await ch.writeValueWithoutResponse(part);
                    else await ch.writeValue(part);
                    await new Promise(r => setTimeout(r, 40)); // jeda antar-chunk
                }
                setTimeout(() => device.gatt.disconnect(), 800);
            } catch (e) {
                if (e.name !== 'NotFoundError') alert('Gagal cetak Bluetooth: ' + e.message);
            }
        }
    </script>
</body>
</html>
