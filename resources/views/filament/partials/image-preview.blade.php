<div class="flex flex-col items-center space-y-2">
    @if ($getState())
        {{-- $getState() diasumsikan sudah berisi string Base64 --}}
        <img src="{{ $getState() }}" alt="Evidence" class="w-64 h-48 rounded border">
    @else
        <div class="w-64 h-48 flex items-center justify-center border rounded bg-gray-100 text-gray-400">
            Belum ada foto
        </div>
    @endif
</div>