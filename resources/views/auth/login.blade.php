<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — {{ config('app.name', 'Bengkel POS') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-brand-dark min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center text-white mb-6">
            <div class="w-12 h-12 rounded-xl bg-brand text-white flex items-center justify-center font-bold text-xl mx-auto mb-3">B</div>
            <h1 class="text-xl font-semibold">{{ \App\Models\Setting::get('nama_bengkel', 'Bengkel POS') }}</h1>
            <p class="text-sm text-gray-400">Silakan masuk untuk melanjutkan</p>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            @if ($errors->any())
                <div class="mb-4 text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-brand focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input name="password" type="password" required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-brand focus:border-transparent">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="rounded border-gray-300"> Ingat saya
                </label>
                <button class="w-full py-2.5 bg-brand text-white rounded-lg font-medium hover:bg-brand-hover">
                    Masuk
                </button>
            </form>
        </div>
        <p class="text-center text-xs text-gray-500 mt-4">© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>
