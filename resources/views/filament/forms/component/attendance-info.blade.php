@if ($getState())
    <div class="p-3 text-sm rounded-lg bg-success-50 text-success-700 border border-success-200">
        {{ $getState() }}
    </div>
@else
    <div class="p-3 text-sm rounded-lg bg-warning-50 text-warning-700 border border-warning-200">
        Tidak ada attendance untuk tanggal ini.
    </div>
@endif
