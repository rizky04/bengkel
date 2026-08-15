<form method="GET" class="flex flex-wrap gap-2 items-end mb-6">
    <div><label class="block text-xs text-gray-500 mb-1">Dari</label><input type="date" name="dari" value="{{ $dari }}" class="form-input"></div>
    <div><label class="block text-xs text-gray-500 mb-1">Sampai</label><input type="date" name="sampai" value="{{ $sampai }}" class="form-input"></div>
    <button class="btn-secondary btn-sm">Terapkan</button>
    <a href="{{ url()->current() }}" class="btn-secondary btn-sm">Reset</a>
</form>
