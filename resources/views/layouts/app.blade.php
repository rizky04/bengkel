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
                // [route, label, ikon, izin]
                $groups = [
                    '' => [
                        ['dashboard', 'Dashboard', 'home', 'dashboard'],
                        ['pos.create', 'Kasir / POS', 'cart', 'pos'],
                        ['transactions.index', 'Transaksi', 'receipt', 'transactions'],
                        ['returns.index', 'Retur', 'inbox', 'returns'],
                        ['reminders.index', 'Pengingat Servis', 'refresh', 'reminders'],
                        ['shifts.index', 'Shift Kasir', 'clipboard', 'shifts'],
                    ],
                    'Master Data' => [
                        ['customers.index', 'Pelanggan', 'users', 'customers'],
                        ['vehicles.index', 'Kendaraan', 'truck', 'vehicles'],
                        ['parts.index', 'Sparepart', 'cube', 'parts'],
                        ['services.index', 'Jasa / Servis', 'wrench', 'services'],
                        ['categories.index', 'Kategori', 'tag', 'categories'],
                        ['suppliers.index', 'Supplier', 'store', 'suppliers'],
                    ],
                    'Inventori' => [
                        ['purchases.index', 'Pembelian', 'inbox', 'purchases'],
                        ['stock.index', 'Stok & Opname', 'clipboard', 'stock', ['stock.index', 'stock.opname*', 'stock.moves', 'stock.card']],
                        ['stock.transfer', 'Transfer Stok', 'refresh', 'stock_transfer', ['stock.transfer*']],
                    ],
                    'Keuangan & Admin' => [
                        ['approvals.index', 'Persetujuan', 'clipboard', 'approvals'],
                        ['promos.index', 'Promo', 'ticket', 'promos'],
                        ['expenses.index', 'Pengeluaran', 'cash', 'expenses'],
                        ['employees.index', 'Karyawan & Gaji', 'usergroup', 'employees'],
                        ['reports.index', 'Laporan', 'chart', 'reports'],
                        ['branches.index', 'Cabang', 'store', 'branches'],
                        ['users.index', 'Pengguna', 'users', 'users'],
                        ['roles.index', 'Role & Akses', 'shield', 'roles'],
                        ['activity.index', 'Log Aktivitas', 'receipt', 'activity'],
                        ['settings.edit', 'Pengaturan', 'cog', 'settings'],
                    ],
                ];
            @endphp

            @php
                // Aktif bila salah satu pola route cocok. Default = "base.*"; menu yang
                // berbagi base (mis. Stok & Transfer sama-sama "stock") memberi pola eksplisit
                // di elemen ke-5 agar tak saling menyalakan.
                $navAktif = function ($it) {
                    $pola = $it[4] ?? [\Illuminate\Support\Str::before($it[0], '.') . '*'];
                    return request()->routeIs(...$pola);
                };
                $pendingApproval = auth()->user()->canAccess('approvals')
                    ? \App\Models\EditRequest::pending()->where('branch_id', current_branch())->count() : 0;
            @endphp

            @foreach ($groups as $label => $items)
                @php
                    $visible = collect($items)->filter(fn ($it) =>
                        auth()->user()->canAccess($it[3])
                        && \Illuminate\Support\Facades\Route::has($it[0]));
                @endphp
                @if ($visible->isNotEmpty())
                    @if ($label)<div class="nav-group">{{ $label }}</div>@endif
                    @foreach ($visible as $it)
                        <a href="{{ route($it[0]) }}"
                           class="nav-link {{ $navAktif($it) ? 'active' : '' }}">
                            @include('partials.icon', ['name' => $it[2]])
                            <span class="flex-1">{{ $it[1] }}</span>
                            @if ($it[0] === 'approvals.index' && $pendingApproval > 0)
                                <span class="ml-auto inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-rose-500 text-white text-xs font-semibold">{{ $pendingApproval }}</span>
                            @endif
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

            @php $branches = \App\Models\Branch::aktif()->orderBy('nama')->get(); @endphp
            @if ($branches->count() > 1 && auth()->user()->isAdmin())
                <form method="POST" action="{{ route('branch.switch') }}" class="hidden sm:flex items-center gap-1.5">
                    @csrf
                    <span class="text-gray-400">@include('partials.icon', ['name' => 'store', 'class' => 'w-4 h-4'])</span>
                    <select name="branch_id" onchange="this.form.submit()" class="rounded-lg border-gray-200 text-sm py-1.5 pr-8 text-gray-700 focus:ring-brand">
                        @foreach ($branches as $br)
                            <option value="{{ $br->id }}" @selected(current_branch() == $br->id)>{{ $br->nama }}</option>
                        @endforeach
                    </select>
                </form>
            @elseif ($branches->count() === 1)
                <span class="hidden sm:flex items-center gap-1.5 text-sm text-gray-500">@include('partials.icon', ['name' => 'store', 'class' => 'w-4 h-4']) {{ $branches->first()->nama }}</span>
            @endif

            <div class="text-sm text-gray-500 hidden lg:block">{{ now()->translatedFormat('l, d F Y') }}</div>
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
