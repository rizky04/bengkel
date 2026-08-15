<?php

if (! function_exists('current_branch')) {
    /** ID cabang aktif: pilihan sesi (admin bisa ganti) → cabang user → cabang pertama. */
    function current_branch(): ?int
    {
        return session('branch_id')
            ?? auth()->user()?->branch_id
            ?? \App\Models\Branch::query()->value('id');
    }
}

if (! function_exists('rupiah')) {
    function rupiah($n): string
    {
        return 'Rp ' . number_format((float) $n, 0, ',', '.');
    }
}

if (! function_exists('csv_download')) {
    /**
     * Unduh CSV (dibuka langsung oleh Excel). BOM UTF-8 supaya karakter Indonesia rapi.
     *
     * @param  array  $headers  baris judul kolom
     * @param  iterable  $rows   tiap baris array nilai
     */
    function csv_download(string $filename, array $headers, iterable $rows): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
