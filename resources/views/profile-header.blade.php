<div class="flex items-center gap-3">
    {{-- Foto Profil --}}
    <div class="flex-shrink-0">
        <img
            src="{{ $photo }}"
            alt="Profile Photo"
            style="width: 30rem; height: 28rem; object-fit: cover; border-radius: 8px; border: 1px solid #d1d5db;"
            onerror="this.src='{{ asset('images/default-avatar.png') }}'">
    </div>

    {{-- Informasi Profil --}}
    {{-- <div class="flex flex-col text-left">
        <h2 class="text-xs font-semibold text-gray-900 dark:text-white uppercase leading-tight">
            {{ $name }}
        </h2>

        <div class="space-y-0 text-[9px] leading-tight mt-0.5">
            <p class="text-gray-600 dark:text-gray-300">
                <span class="font-semibold">Divisi:</span> {{ $division }}
            </p>
            <p class="text-gray-600 dark:text-gray-300">
                <span class="font-semibold">Unit:</span> {{ $unit }}
            </p>
            <p class="text-gray-600 dark:text-gray-300">
                <span class="font-semibold">Jabatan:</span> {{ $position }}
            </p>
        </div>
    </div> --}}
</div>