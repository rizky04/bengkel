# PRD — Aplikasi Bengkel (Service + Kasir/POS + Inventori Lengkap)

**Versi:** 2.2
**Tanggal:** 2026-08-14
**Status:** Draft
**Stack:** Laravel + MySQL + Blade + JavaScript + Tailwind CSS (aplikasi POS bengkel berbasis kasir, walk-in)

---

## 1. Ringkasan

Aplikasi manajemen bengkel motor & mobil yang menyatukan **tiga fungsi inti dalam satu sistem**:

1. **Service / Work Order** — kelola kendaraan masuk, keluhan, pengerjaan mekanik, jasa & part terpakai.
2. **Kasir / POS** — transaksi di tempat oleh kasir: servis maupun penjualan sparepart langsung, pembayaran, cetak nota.
3. **Inventori Lengkap** — stok sparepart, pembelian ke supplier, opname, kartu stok, mutasi, minimum stock alert.

Semua dijalankan **langsung oleh kasir** di lokasi. **Tidak ada booking online** — model murni datang langsung (walk-in). Menggantikan buku & nota kertas agar order, stok, dan uang tercatat rapi, akurat, dan bisa dilaporkan otomatis.

**Target pengguna:** bengkel kecil–menengah, 1–3 cabang, 2–15 mekanik.

## 2. Masalah yang Diselesaikan

| Masalah saat ini | Dampak | Solusi aplikasi |
|---|---|---|
| Riwayat servis pelanggan di buku, hilang/susah dicari | Tidak bisa rekomendasi servis berkala | Riwayat per kendaraan tersimpan permanen |
| Stok tidak akurat, tidak tahu part habis | Part kosong saat dibutuhkan / overstock | Stok real-time + alert minimum |
| Nota manual, rawan salah hitung | Selisih uang, sulit rekap | Perhitungan otomatis + nota digital |
| Tidak tahu omzet/laba harian | Sulit ambil keputusan | Laporan otomatis harian/bulanan |
| Tidak tahu produktivitas mekanik | Sulit evaluasi | Rekap jasa per mekanik |

## 3. Tujuan & Metrik Keberhasilan

| Tujuan | Metrik |
|---|---|
| Digitalisasi semua transaksi | 100% order & penjualan lewat sistem |
| Stok akurat | Selisih stok fisik vs sistem < 3% |
| Rekap keuangan otomatis | Laporan tersedia tanpa hitung manual |
| Efisiensi kasir | Waktu buat transaksi < 2 menit |
| Retensi pelanggan | Riwayat & pengingat servis tersedia |

**Non-tujuan (v1):** booking/antrean online, **akuntansi penuh (jurnal double-entry/neraca/pajak PPN formal)**, marketplace part, aplikasi mobile pelanggan. *(Pengeluaran, gaji karyawan, dan laba-rugi sederhana **termasuk cakupan** — lihat 5.9.)*

## 4. Peran Pengguna (Roles & Hak Akses)

| Fitur | Owner/Admin | Kasir | Mekanik |
|---|:--:|:--:|:--:|
| Dashboard & laporan lengkap | ✅ | ✅ (operasional) | — |
| Kelola user & role | ✅ | — | — |
| Master data (part, jasa, harga) | ✅ | ✅ (edit terbatas) | — |
| POS / buat transaksi | ✅ | ✅ | — |
| Pembayaran & nota | ✅ | ✅ | — |
| Manajemen inventori | ✅ | ✅ | — |
| Kerjakan order (jasa & part) | ✅ | — | ✅ |
| Keuangan: pengeluaran & gaji | ✅ | — | — |
| Laporan keuangan (laba-rugi, arus kas) | ✅ | — | — |
| Setting sistem, pajak, cabang | ✅ | — | — |

*Modul keuangan (pengeluaran, gaji, laba-rugi) sensitif → default hanya Owner/Admin. Bisa diberikan ke kasir tertentu via hak akses.*

*Peran gudang tidak dipisah di v1 — inventori ditangani kasir/admin. Bisa dipisah di P1 jika volume besar.*

---

## 5. Fitur Lengkap

### 5.1 Master Data

- **Pelanggan** — nama, HP, alamat, catatan. 1 pelanggan bisa punya banyak kendaraan.
- **Kendaraan** — plat, jenis (motor/mobil), merk, tipe, tahun, no. rangka, no. mesin, warna. Terhubung ke pelanggan.
- **Sparepart/Barang** — kode/SKU, nama, kategori, satuan, harga beli, harga jual, stok, stok minimum, lokasi rak, supplier utama.
- **Jasa/Servis** — nama, kategori (servis ringan/berat/dll), tarif standar.
- **Supplier** — nama, kontak, alamat, catatan.
- **Kategori** — untuk part & jasa (memudahkan filter/laporan).

### 5.2 Service / Work Order

- Buat work order: pilih pelanggan+kendaraan (atau input baru), keluhan/gejala, assign mekanik.
- **Status alur:** `Antri → Dikerjakan → Selesai → Lunas`. (batal juga tersedia)
- Tambah item: **jasa** (dari master atau custom) + **part** terpakai. Part memotong stok otomatis saat dipakai.
- Estimasi biaya awal → total akhir setelah pengerjaan.
- Catatan mekanik / hasil pemeriksaan.
- Cetak **estimasi/perintah kerja** untuk mekanik.
- Riwayat lengkap per kendaraan (timeline servis sebelumnya).

### 5.3 Kasir / POS

Layar kasir cepat, menangani dua jenis transaksi:

- **Transaksi Servis** — lanjutan dari work order yang `Selesai`, tinggal bayar.
- **Penjualan Part Langsung** — walk-in beli oli/part tanpa servis. Cari part by kode/nama/scan, tambah qty, keranjang real-time.
- **Platform/Channel penjualan** — setiap transaksi wajib pilih sumber penjualan: **Kasir/Offline**, **Shopee**, **Tokopedia**, **WhatsApp**, **Lainnya** (daftar bisa diatur di pengaturan). Dipakai untuk laporan per platform.
- Keranjang: subtotal, diskon per item & per transaksi (nominal/%), **promo otomatis** (dari master promo), pajak (opsional, bisa di-set).
- Hitung total & **kembalian otomatis**.
- Support **1 transaksi = beberapa item** (jasa + part campur).
- Tahan/hold transaksi (parkir sementara, lanjut nanti).

### 5.4 Pembayaran & Nota

- Metode: **tunai** (input uang → kembalian), **transfer**, **QRIS**, **kartu/debit**.
- **Pembayaran sebagian (DP) / bon** → status `Belum Lunas`, sisa piutang tercatat.
- **Split payment** (mis. sebagian tunai + sebagian transfer) — opsional.
- Nota otomatis (no. nota unik) → cetak **thermal 58/80mm** atau **A5**, + export **PDF**.
- Void/refund transaksi (dengan alasan & hak akses admin) → stok dikembalikan.

### 5.5 Inventori Lengkap

- **Stok real-time** — berkurang otomatis saat part dijual/dipakai di servis.
- **Penerimaan barang / Pembelian (PO sederhana)** — input barang masuk dari supplier: qty, harga beli, update stok + HPP.
- **Penyesuaian stok / Stok Opname** — koreksi selisih fisik vs sistem, dengan alasan.
- **Kartu stok (mutasi)** — histori masuk/keluar/adjust per part, bisa ditelusuri sampai referensi transaksi.
- **Alert stok minimum** — badge/notifikasi part di bawah minimum, tampil di dashboard & saat transaksi (toast peringatan ketika menjual part yang mau habis).
- **Transfer stok antar cabang** *(jika multi-cabang aktif — P1)*.
- **Nilai persediaan** — total nilai stok (qty × harga beli) untuk laporan aset.
- Riwayat harga beli per pembelian (tracking HPP).

### 5.6 Laporan Lengkap

- **Penjualan/Omzet** — per hari/bulan/rentang custom, per jenis (servis vs part), **per platform/channel** (Kasir/Shopee/Tokopedia/dll).
- **Laba kotor** — jasa + margin part (harga jual − harga beli), setelah diskon.
- **Rekap pembayaran** — per metode (tunai/transfer/QRIS/kartu).
- **Laporan diskon & promo** — total diskon diberikan, promo terpakai & efektivitasnya.
- **Laporan part** — terlaris, slow-moving, sisa stok, nilai persediaan.
- **Kartu stok & mutasi** per part/periode.
- **Piutang / bon** — daftar belum lunas per pelanggan.
- **Produktivitas mekanik** — jumlah order & nilai jasa per mekanik.
- **Tutup kasir (closing) harian** — total transaksi, kas masuk per metode, selisih kas.
- **Riwayat servis kendaraan** — semua servis per plat.
- Semua laporan bisa **export PDF & Excel**, filter periode & cabang.

### 5.7 Dashboard

Ringkasan cepat halaman utama:
- Omzet hari ini & bulan ini, jumlah transaksi.
- Order aktif (antri/dikerjakan).
- Part di bawah stok minimum.
- Grafik omzet 7/30 hari terakhir.
- Piutang outstanding.
- Ringkasan keuangan bulan ini: pendapatan, pengeluaran, laba/rugi (untuk Owner/Admin).

### 5.8 Promo & Diskon

Diskon bisa diterapkan **di semua transaksi** (servis maupun penjualan part), dengan dua cara:

- **Diskon manual** — kasir isi diskon per item atau per transaksi (nominal Rp / persen %) langsung di POS. Batas maksimal diskon bisa di-set per role (kasir vs admin).
- **Promo terjadwal (master promo)** — admin buat aturan promo yang otomatis terpakai saat syarat terpenuhi:
  - **Jenis diskon:** persen (%), potongan nominal (Rp), atau harga khusus.
  - **Cakupan:** semua transaksi, kategori tertentu, part/jasa tertentu, atau platform tertentu.
  - **Syarat:** minimal belanja, minimal qty, atau berlaku untuk semua.
  - **Periode:** tanggal & jam mulai–selesai (promo kedaluwarsa otomatis).
  - **Kode voucher** (opsional) — pelanggan sebut kode → diskon aktif.
  - **Kuota** (opsional) — batas jumlah pemakaian.
  - **Status:** aktif/nonaktif (bisa dimatikan sewaktu-waktu).
- Beberapa promo bisa berlaku; sistem pilih yang paling menguntungkan / sesuai prioritas (hindari dobel diskon kecuali diizinkan).
- Diskon & promo yang terpakai **tercatat di nota** dan masuk **laporan** (nilai diskon, promo terlaris, efektivitas).

### 5.9 Keuangan & Akunting Sederhana

Mencatat uang keluar-masuk operasional bengkel, bukan akuntansi penuh (jurnal/neraca), tapi cukup untuk tahu **laba-rugi riil** dan **arus kas**.

**Pengeluaran / Biaya**
- Catat pengeluaran operasional: tanggal, kategori, nominal, keterangan, metode bayar, bukti (opsional upload nota).
- **Kategori pengeluaran** (bisa diatur): gaji karyawan, sewa tempat, listrik/air, beli alat/perlengkapan, konsumsi, transport, marketing, lain-lain.
- Pembelian sparepart ke supplier (dari modul inventori) otomatis masuk sebagai pengeluaran/HPP — tidak dobel input.

**Gaji Karyawan (Payroll sederhana)**
- Data karyawan: nama, jabatan (kasir/mekanik/dll), gaji pokok, status.
- Catat pembayaran gaji per periode (bulanan): gaji pokok + bonus/tunjangan − potongan = gaji dibayar.
- **Komisi mekanik (opsional)** — hitung dari nilai jasa yang dikerjakan (persen), tampil sebagai usulan saat bayar gaji.
- Riwayat penggajian per karyawan; pembayaran gaji tercatat sebagai pengeluaran kategori "gaji".

**Kas & Arus Kas**
- **Kas masuk** = pembayaran transaksi (servis + penjualan).
- **Kas keluar** = pengeluaran + gaji + pembelian.
- Saldo kas berjalan; arus kas per periode.

**Laporan Keuangan**
- **Laba-Rugi sederhana** = Pendapatan − HPP (harga beli part terjual) − Pengeluaran operasional (termasuk gaji) → laba/rugi bersih per periode.
- **Rekap pengeluaran** per kategori & periode.
- **Rekap gaji** per karyawan & bulan.
- **Arus kas** (kas masuk vs keluar) harian/bulanan.
- Export PDF & Excel.

### 5.10 Pengaturan Sistem

- Profil bengkel (nama, logo, alamat, HP — untuk header nota).
- Pajak (aktif/nonaktif, %), format no. nota.
- **Daftar platform/channel penjualan** (Kasir, Shopee, Tokopedia, WhatsApp, dll — bisa tambah/edit).
- **Pengaturan diskon & promo** (batas diskon per role, izin dobel promo).
- **Kategori pengeluaran** & **data karyawan/gaji**.
- Kelola user, role, & (opsional) cabang.
- Metode pembayaran aktif.

---

## 6. Prioritas Rilis

**P0 (MVP wajib):** Master data, Work Order, POS servis + jual part, **platform/channel penjualan**, **diskon manual + master promo**, Pembayaran & nota, Inventori (stok, pembelian, opname, kartu stok, alert), **Keuangan (pengeluaran, gaji karyawan, laba-rugi & arus kas sederhana)**, Laporan inti (omzet per platform, laba, diskon/promo, stok, tutup kasir), Dashboard, Role & auth.

**P1 (berikutnya):** Riwayat & pengingat servis berkala, produktivitas mekanik detail, komisi mekanik otomatis, multi-cabang + stok per cabang, shift kasir (buka/tutup kas per shift), split payment, kode voucher & kuota promo.

**P2 (nanti):** Integrasi WhatsApp (nota & reminder otomatis), barcode/scanner, dashboard analitik lanjutan, program loyalitas/poin.

*Booking online / antrean: **di luar cakupan** — aplikasi murni POS walk-in.*

---

## 7. Alur Utama

**A. Servis (kendaraan masuk → lunas)**
1. Kasir input/pilih pelanggan + kendaraan.
2. Buat work order + keluhan, assign mekanik → `Antri`.
3. Mekanik kerjakan, tambah jasa & part → `Dikerjakan`. (stok part terpotong)
4. Selesai → total dihitung → `Selesai`.
5. Kasir terima pembayaran → cetak nota → `Lunas`.

**B. Penjualan part langsung (walk-in)**
1. Kasir buka POS, cari part, tambah ke keranjang.
2. Total dihitung → bayar (kembalian otomatis) → cetak nota. Stok terpotong.

**C. Penerimaan stok**
1. Kasir/admin input pembelian dari supplier (qty + harga beli) → stok bertambah, kartu stok & HPP tercatat.

**D. Stok opname**
1. Hitung fisik → input jumlah aktual → sistem catat selisih sebagai penyesuaian.

**E. Tutup kasir harian**
1. Akhir hari kasir buka menu closing → sistem tampilkan total per metode → cek fisik kas → catat selisih.

---

## 8. Model Data (ERD ringkas)

Transaksi servis & penjualan part disatukan pada `transactions` (dibedakan `tipe`) agar POS & work order berbagi alur pembayaran/nota.

```
users          (id, nama, email, password, role, cabang_id, aktif)
customers      (id, nama, hp, alamat, catatan)
vehicles       (id, customer_id, plat, jenis, merk, tipe, tahun, no_rangka, no_mesin, warna)
categories     (id, nama, tipe[part|jasa])
suppliers      (id, nama, hp, alamat, catatan)
parts          (id, kode, nama, category_id, satuan, harga_beli, harga_jual,
                stok, stok_min, lokasi_rak, supplier_id)
services       (id, nama, category_id, tarif)

platforms      (id, nama, aktif)   // Kasir, Shopee, Tokopedia, WhatsApp, dll
promos         (id, nama, kode?, jenis[persen|nominal|harga_khusus], nilai,
                cakupan[semua|kategori|item|platform], target_id?,
                min_belanja?, min_qty?, mulai, selesai, kuota?, terpakai, aktif)

transactions   (id, no_nota, tipe[servis|penjualan], platform_id, customer_id?,
                vehicle_id?, mekanik_id?, keluhan?, catatan_mekanik?, status,
                subtotal, diskon, promo_id?, pajak, total, cabang_id, user_id, tgl)
tx_items       (id, transaction_id, tipe[jasa|part], ref_id?, nama, qty, harga,
                diskon, subtotal)
payments       (id, transaction_id, jumlah, metode, tgl_bayar)   // >1 utk DP/split

purchases      (id, no, supplier_id, total, user_id, tgl)
purchase_items (id, purchase_id, part_id, qty, harga_beli, subtotal)

stock_moves    (id, part_id, tipe[in|out|adjust], qty, saldo, ref_tipe, ref_id,
                keterangan, user_id, tgl)

expense_cats   (id, nama)   // gaji, sewa, listrik, alat, dll
expenses       (id, tanggal, expense_cat_id, nominal, keterangan, metode,
                bukti?, ref_tipe?, ref_id?, user_id)   // ref utk link ke gaji/pembelian
employees      (id, user_id?, nama, jabatan, gaji_pokok, komisi_persen?, aktif)
salaries       (id, employee_id, periode, gaji_pokok, bonus, komisi, potongan,
                total_dibayar, tgl_bayar, expense_id)

settings       (id, key, value)   // profil bengkel, pajak, format nota, dll
cabang         (id, nama, alamat)  // opsional / multi-cabang P1
```

**Relasi kunci:**
- `customer` 1—N `vehicles`
- `transaction` 1—N `tx_items`, 1—N `payments`
- `part` 1—N `stock_moves` (kartu stok)
- `purchase` 1—N `purchase_items`
- setiap keluar/masuk part → 1 baris `stock_moves` (sumber kebenaran stok)

---

## 9. Persyaratan Non-Fungsional

- **Platform:** web app, desktop-first (kasir pakai PC/laptop). Responsive agar owner cek via HP.
- **Multi-user** dengan login & role, sesi aman.
- **Performa:** cari part & buat transaksi terasa instan (<1 dtk) sampai ±10rb SKU.
- **Integritas stok:** perubahan stok selalu lewat `stock_moves` dalam DB transaction (anti selisih/race).
- **Cetak:** thermal 58/80mm & A5, + PDF.
- **Backup:** DB harian otomatis (`mysqldump` cron).
- **Audit:** transaksi & perubahan stok mencatat user + waktu.
- **Keamanan:** password hash, otorisasi per role, proteksi CSRF (bawaan Laravel).
- **Ketersediaan:** asumsi ada internet lokal; offline mode tidak wajib v1.

## 10. Stack Teknis

| Lapis | Pilihan | Alasan |
|---|---|---|
| Backend | **Laravel (PHP)** | matang, cepat untuk CRUD + laporan + role |
| Database | **MySQL** | standar, cukup untuk skala bengkel |
| Frontend | **Blade + JavaScript + Tailwind CSS** | server-rendered, styling utility-first, JS untuk interaksi POS |
| Interaktivitas | **Alpine.js** (ringan) + `fetch`/AJAX | keranjang real-time, cari part, hitung total tanpa reload — tanpa SPA/React |
| Notifikasi/Alert | **SweetAlert2** (konfirmasi/sukses/error) + **toast** (notiv/Toastify) | alert yang rapi & modern, ganti `alert()` bawaan browser |
| Auth/role | Laravel auth + policy / `spatie/laravel-permission` | role granular |
| PDF nota | `barryvdh/laravel-dompdf` | nota & laporan PDF |
| Export | `maatwebsite/excel` | laporan Excel |
| Build asset | **Vite** (bawaan Laravel) | bundling Tailwind + JS |
| Deploy | VPS / hosting PHP | murah, backup cron |

*Frontend sengaja tanpa Livewire/React — cukup Blade + Tailwind + Alpine.js/JS untuk POS. Menambah SPA hanya jika UI terbukti butuh lebih.*

### 10.1 UX Notifikasi & Alert

Semua feedback ke pengguna pakai komponen yang rapi, konsisten, bukan `alert()`/`confirm()` bawaan:

- **Konfirmasi aksi berisiko** (hapus, void, refund, opname) → modal **SweetAlert2** ("Yakin hapus? Ya/Batal").
- **Sukses** (transaksi tersimpan, stok masuk) → **toast hijau** pojok kanan atas, auto-hilang.
- **Error/validasi** (stok kurang, field kosong) → **toast merah** / inline error di form.
- **Peringatan** (jual part di bawah stok minimum, harga jual < harga beli) → **toast kuning**.
- **Loading** saat proses (cetak/simpan) → indikator/spinner, tombol disable agar tak double-submit.
- Alert stok minimum tampil sebagai **badge angka** di menu inventori + panel di dashboard.

## 11. Asumsi & Risiko

| Asumsi/Risiko | Mitigasi |
|---|---|
| Bengkel punya PC + internet dasar | Jika tidak → pertimbangkan offline (naikkan scope) |
| Adopsi mekanik butuh UI simpel | Input cepat, minim ketik, tombol besar |
| Akurasi stok bergantung disiplin input | Wajibkan part dicatat sebelum transaksi bisa `Lunas` |
| Kasir kelola inventori (rangkap) | Cukup untuk skala kecil; pisah role gudang di P1 |

---

## 12. Rencana Implementasi (fase)

1. **Fondasi** — setup Laravel, auth, role, layout, master data (part, jasa, pelanggan, kendaraan, supplier).
2. **Inventori** — model stok + `stock_moves`, pembelian, opname, kartu stok, alert minimum.
3. **POS & Work Order** — transaksi servis + penjualan part, keranjang JS (Alpine.js), pemotongan stok.
4. **Pembayaran, Promo & Nota** — pembayaran (tunai/transfer/QRIS), diskon & master promo, platform penjualan, DP/piutang, nota thermal & PDF.
5. **Keuangan** — pengeluaran + kategori, gaji karyawan, kas/arus kas, laba-rugi sederhana.
6. **Laporan & Dashboard** — omzet per platform, laba, diskon/promo, stok, keuangan, tutup kasir, export.
7. **Polish** — pengaturan sistem, hak akses akhir, uji end-to-end.

---

*Cakupan v1 = aplikasi bengkel lengkap berbasis kasir: service (work order) + POS + inventori penuh + promo/diskon + keuangan (pengeluaran, gaji, laba-rugi sederhana) + laporan. Booking online dihapus dari scope. Akuntansi penuh (jurnal double-entry/neraca), WA otomatis, barcode, dan multi-cabang ditunda ke P1/P2 agar MVP cepat jalan.*
