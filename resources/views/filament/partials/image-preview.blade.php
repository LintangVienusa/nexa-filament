@if ($getState())
    <img src="{{ asset('storage/' . $getState()) }}" alt="Evidence" class="w-64 h-48 rounded border" />
@else
    <div class="w-64 h-48 flex items-center justify-center border rounded bg-gray-100 text-gray-400">
        Belum ada foto
    </div>
@endif