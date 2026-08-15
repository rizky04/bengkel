<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'Bengkel POS') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-surface text-gray-800 antialiased">
<div class="flex h-screen overflow-hidden">

    {{-- Backdrop sidebar (HP) --}}
    <div id="sidebar-backdrop" onclick="toggleSidebar(false)"
         class="hidden fixed inset-0 bg-black/40 z-30 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside id="sidebar"
           class="w-64 bg-white border-r border-gray-200 flex flex-col shrink-0 fixed inset-y-0 left-0 z-40 -translate-x-full transition-transform duration-200 lg:static lg:translate-x-0">
        <div class="h-16 flex items-center gap-2.5 px-5">
            <span class="w-8 h-8 rounded-lg bg-brand text-white flex items-center justify-center font-bold text-sm">B</span>
            <span class="font-semibold text-gray-800 truncate">{{ \App\Models\Setting::get('nama_bengkel', 'Bengkel POS') }}</span>
        </div>

        <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-0.5">
            @php
                $groups = [
                    '' => [
                        ['dashboard', 'Dashboard', 'home', null],
                        ['pos.create', 'Kasir / POS', 'cart', null],
                        ['transactions.index', 'Transaksi', 'receipt', null],
                        ['shifts.index', 'Shift Kasir', 'clipboard', null],
                    ],
                    'Master Data' => [
                        ['customers.index', 'Pelanggan', 'users', null],
                        ['vehicles.index', 'Kendaraan', 'truck', null],
                        ['parts.index', 'Sparepart', 'cube', null],
                        ['services.index', 'Jasa / Servis', 'wrench', null],
                        ['categories.index', 'Kategori', 'tag', null],
                        ['suppliers.index', 'Supplier', 'store', null],
                    ],
                    'Inventori' => [
                        ['purchases.index', 'Pembelian', 'inbox', null],
                        ['stock.index', 'Stok & Opname', 'clipboard', null],
                    ],
                    'Keuangan & Lainnya' => [
                        ['promos.index', 'Promo', 'ticket', 'admin'],
                        ['expenses.index', 'Pengeluaran', 'cash', 'admin'],
                        ['employees.index', 'Karyawan & Gaji', 'usergroup', 'admin'],
                        ['reports.index', 'Laporan', 'chart', 'admin'],
                        ['users.index', 'Pengguna', 'users', 'admin'],
                        ['activity.index', 'Log Aktivitas', 'receipt', 'admin'],
                        ['settings.edit', 'Pengaturan', 'cog', 'admin'],
                    ],
                ];
            @endphp

            @foreach ($groups as $label => $items)
                @php
                    $visible = collect($items)->filter(fn ($it) =>
                        (! $it[3] || auth()->user()->isAdmin())
                        && \Illuminate\Support\Facades\Route::has($it[0]));
                @endphp
                @if ($visible->isNotEmpty())
                    @if ($label)<div class="nav-group">{{ $label }}</div>@endif
                    @foreach ($visible as [$routeName, $text, $icon, $role])
                        <a href="{{ route($routeName) }}"
                           class="nav-link {{ request()->routeIs(\Illuminate\Support\Str::before($routeName, '.').'*') ? 'active' : '' }}">
                            @include('partials.icon', ['name' => $icon])
                            {{ $text }}
                        </a>
                    @endforeach
                @endif
            @endforeach
        </nav>

        <div class="border-t border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-full bg-brand-light text-brand flex items-center justify-center font-semibold text-sm">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium text-gray-800 truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-gray-400 capitalize">{{ auth()->user()->role }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button title="Keluar" class="text-gray-400 hover:text-gray-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center px-4 lg:px-6 gap-3 shrink-0">
            <button type="button" aria-label="Menu" class="lg:hidden text-gray-600" onclick="toggleSidebar(true)">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="flex-1 min-w-0 text-lg font-semibold text-gray-800 truncate">@yield('header', 'Dashboard')</h1>
            <div class="text-sm text-gray-500 hidden sm:block">{{ now()->translatedFormat('l, d F Y') }}</div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-6">
            @yield('content')
        </main>
    </div>
</div>

{{-- Flash toast (SweetAlert2) --}}
<script>
    function toggleSidebar(open) {
        document.getElementById('sidebar').classList.toggle('-translate-x-full', !open);
        document.getElementById('sidebar-backdrop').classList.toggle('hidden', !open);
    }

    const Toast = Swal.mixin({
        toast: true, position: 'top-end', showConfirmButton: false,
        timer: 3000, timerProgressBar: true,
    });
    @if (session('success')) Toast.fire({ icon: 'success', title: @json(session('success')) }); @endif
    @if (session('error'))   Toast.fire({ icon: 'error',   title: @json(session('error')) }); @endif
    @if (session('warning')) Toast.fire({ icon: 'warning', title: @json(session('warning')) }); @endif
    @if ($errors->any())     Toast.fire({ icon: 'error',   title: @json($errors->first()) }); @endif

    document.addEventListener('submit', function (e) {
        const f = e.target.closest('form[data-confirm]');
        if (!f || f.dataset.confirmed) return;
        e.preventDefault();
        Swal.fire({
            title: f.dataset.confirm || 'Yakin?', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal',
            confirmButtonColor: '#dc2626',
        }).then(r => { if (r.isConfirmed) { f.dataset.confirmed = 1; f.submit(); } });
    });
</script>
<style>[x-cloak]{display:none!important}</style>
@stack('scripts')
</body>
</html>
