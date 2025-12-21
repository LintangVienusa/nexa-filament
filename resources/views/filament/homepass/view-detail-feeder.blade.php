

<div class="space-y-4 text-sm ">
{{-- <div class="max-w-lg mx-auto bg-gray-50 border border-gray-300 rounded-md p-4 space-y-4 text-sm shadow-sm"> --}}

    <!-- ================= INFO ================= -->
    <div class="space-y-4 max-w-md">
        <table  style="width:100%; border-collapse:collapse; ">
        <tr>
            <div>
                <label class="block text-sm font-medium ">Site</label>
                <input type="text" value="{{ $record->site }}" readonly
                    class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm sm:text-sm cursor-not-allowed bg-transparent">
            </div>
        </tr>
            <tr>
                <td>
                   
                    <div>
                        <label class="block text-sm font-medium ">Feeder Name</label>
                        <input type="text" value="{{ $record->feeder_name }}" readonly
                            class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm sm:text-sm cursor-not-allowed bg-transparent">
                    </div>
                </td>
            </tr>
            
        </table>
    </div>

    <!-- ================= CATATAN ================= -->
    <div>
        <label class="block text-sm font-medium ">Catatan:</label>
        <textarea readonly
        class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm sm:text-sm cursor-not-allowed bg-transparent"
        rows="4">{{ $record->notes ?: '-' }}</textarea>
    </div>

    <!-- ================= FOTO ================= -->
    <table border="1" style="width:100%; border-collapse:collapse; text-align:center">

        <tr style="height:30px; font-weight:bold">
            <td>FOTO Pulling Cable A   
                <div x-data>
                    <button type="button" 
                        @click.prevent="
                            fetch('/api/rotate-photo', { 
                                method: 'POST', 
                                headers: {'Content-Type':'application/json'}, 
                                body: JSON.stringify({ path: '{{ $record->pulling_cable }}' }) 
                            })
                            .then(res => res.json())
                            .then(res => {
                                if(res.success){
                                    const img = document.getElementById('foto1');
                                    img.src = img.src.split('?')[0] + '?v=' + new Date().getTime();
                                } else {
                                    alert(res.error || 'Gagal rotate');
                                }
                            })
                            .catch(err => alert(err))
                        "
                    >
                        🔄 Rotate
                    </button>
                </div>
            </td>

            <td>FOTO Pulling Cable B
                <div x-data>
                    <button type="button" 
                        @click.prevent="
                            fetch('/api/rotate-photo', { 
                                method: 'POST', 
                                headers: {'Content-Type':'application/json'}, 
                                body: JSON.stringify({ path: '{{ $record->pulling_cable_b }}' }) 
                            })
                            .then(res => res.json())
                            .then(res => {
                                if(res.success){
                                    const img = document.getElementById('foto2');
                                    img.src = img.src.split('?')[0] + '?v=' + new Date().getTime();
                                } else {
                                    alert(res.error || 'Gagal rotate');
                                }
                            })
                            .catch(err => alert(err))
                        "
                    >
                        🔄 Rotate
                    </button>
                </div>
            </td>
        </tr>

        <tr>
            <td style="padding:8px; width:50%;">
                <div style="
                    width:320px;
                    height:360px;
                    margin:auto;
                    border:1px solid #ccc;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    overflow:hidden;
                ">
                    @if ($record->pulling_cable)
                        <img id="foto1" src="{{ asset('storage/'.$record->pulling_cable) }}?v={{ now()->timestamp }}" style="width:420px;height:460px;object-fit:contain;">
   
                         
                    @else
                        <span style="font-size:12px;color:#999">Tidak ada foto</span>
                    @endif
                </div>
            </td>

            <td style="padding:3px; width:50%;">
                <div style="
                    width:320px;
                    height:360px;
                    margin:auto;
                    border:1px solid #ccc;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    overflow:hidden;
                ">
                    @if ($record->pulling_cable_b)
                        <img id="foto2" src="{{ asset('storage/'.$record->pulling_cable_b) }}?v={{ now()->timestamp }}" 
                        style="width:100%;height:100%;object-fit:contain;">
   
                        
                    @else
                        <span style="font-size:12px;color:#999">Tidak ada foto</span>
                    @endif
                </div>
            </td>
        </tr>
        <tr style="height:30px; font-weight:bold">
            <td>FOTO Instalasi
                <div x-data>
                    <button type="button" 
                        @click.prevent="
                            fetch('/api/rotate-photo', { 
                                method: 'POST', 
                                headers: {'Content-Type':'application/json'}, 
                                body: JSON.stringify({ path: '{{ $record->instalasi }}' }) 
                            })
                            .then(res => res.json())
                            .then(res => {
                                if(res.success){
                                    const img = document.getElementById('foto3');
                                    img.src = img.src.split('?')[0] + '?v=' + new Date().getTime();
                                } else {
                                    alert(res.error || 'Gagal rotate');
                                }
                            })
                            .catch(err => alert(err))
                        "
                    >
                        🔄 Rotate
                    </button>
                </div>
            </td>
        </tr>

        <tr>
            <td style="padding:8px; width:50%;">
                <div style="
                    width:320px;
                    height:360px;
                    margin:auto;
                    border:1px solid #ccc;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    overflow:hidden;
                ">
                    @if ($record->instalasi)
                        <img id="foto3" src="{{ asset('storage/'.$record->instalasi) }}?v={{ now()->timestamp }}" 
                        style="width:100%;height:100%;object-fit:contain;">
                        
                    @else
                        <span style="font-size:12px;color:#999">Tidak ada foto</span>
                    @endif
                </div>
            </td>

        </tr>
        
    </table>
</div>

<script>

    function rotatePhoto(path, imgId) {
        const button = event.currentTarget;
        button.disabled = true;

        fetch("/api/rotate-photo", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ path: path })
        })
        .then(res => res.json())
        .then(res => console.log(res));
    }
</script>