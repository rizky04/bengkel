@php
    $fLabel = $label ?? \Illuminate\Support\Str::headline($name);
    $fType = $type ?? 'text';
    $fRequired = $required ?? false;
    $fPlaceholder = $placeholder ?? null;
    $fOptions = $options ?? [];
    $fVal = old($name, $value ?? null);
@endphp
<div>
    <label for="{{ $name }}" class="form-label">
        {{ $fLabel }}@if ($fRequired)<span class="text-red-500"> *</span>@endif
    </label>

    @if ($fType === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="2" placeholder="{{ $fPlaceholder }}" class="form-input">{{ $fVal }}</textarea>
    @elseif ($fType === 'select')
        <select id="{{ $name }}" name="{{ $name }}" class="form-input">
            @if ($fPlaceholder)<option value="">{{ $fPlaceholder }}</option>@endif
            @foreach ($fOptions as $optVal => $optLabel)
                <option value="{{ $optVal }}" @selected((string) $fVal === (string) $optVal)>{{ $optLabel }}</option>
            @endforeach
        </select>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $fType }}" value="{{ $fVal }}"
               placeholder="{{ $fPlaceholder }}" class="form-input">
    @endif

    @error($name)<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
