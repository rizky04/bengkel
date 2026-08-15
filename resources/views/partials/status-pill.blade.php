@php
    $dotColors = [
        'antri' => 'bg-gray-400', 'dikerjakan' => 'bg-blue-500', 'selesai' => 'bg-amber-500',
        'lunas' => 'bg-emerald-500', 'batal' => 'bg-rose-500',
        'new' => 'bg-blue-500', 'quoted' => 'bg-purple-500', 'closed' => 'bg-orange-500',
    ];
    $dot = $dotColors[strtolower($status)] ?? 'bg-gray-400';
@endphp
<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-gray-200 bg-white text-xs font-medium text-gray-600 whitespace-nowrap">
    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>{{ ucfirst($status) }}
</span>
