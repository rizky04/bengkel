@php
    // Dropdown ber-search untuk pilihan banyak. Pakai:
    // @include('partials.search-select', ['name'=>'customer_id','label'=>'Pelanggan','value'=>$x,'options'=>$col->pluck('nama','id'),'required'=>true])
    $ssVal = old($name, $value ?? '');
    $ssLabelText = $label ?? \Illuminate\Support\Str::headline($name);
    $ssPlaceholder = $placeholder ?? 'Pilih…';
    $ssList = collect($options)->map(fn ($lbl, $val) => ['value' => (string) $val, 'label' => (string) $lbl])->values();
@endphp
<div x-data="searchSelect({ options: {{ \Illuminate\Support\Js::from($ssList) }}, selected: @js((string) $ssVal) })"
     @click.outside="open=false" class="relative">
    <label class="form-label">{{ $ssLabelText }}@if ($required ?? false)<span class="text-red-500"> *</span>@endif</label>
    <input type="hidden" name="{{ $name }}" :value="selected">

    <button type="button" @click="toggle()" class="form-input flex items-center justify-between gap-2 text-left">
        <span class="truncate" :class="!selected && 'text-gray-400'" x-text="label() || @js($ssPlaceholder)"></span>
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
    </button>

    <div x-show="open" x-cloak class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg">
        <div class="p-2 border-b border-gray-100">
            <input x-ref="q" x-model="q" type="text" placeholder="Cari…"
                   class="w-full rounded-lg border-gray-300 text-sm focus:ring-brand focus:border-transparent">
        </div>
        <div class="max-h-56 overflow-y-auto py-1">
            <button type="button" @click="clear()" class="w-full text-left px-3 py-2 text-sm text-gray-400 hover:bg-gray-50">{{ $ssPlaceholder }}</button>
            <template x-for="o in available" :key="o.value">
                <button type="button" @click="pick(o)"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-brand-light"
                        :class="String(o.value) === String(selected) && 'bg-brand-light text-brand font-medium'"
                        x-text="o.label"></button>
            </template>
            <div x-show="!available.length" class="px-3 py-3 text-sm text-gray-400">Tidak ditemukan.</div>
        </div>
    </div>

    @error($name)<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
