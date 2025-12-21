

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
                        <label class="block text-sm font-medium ">ID Pelanggan</label>
                        <input type="text" value="{{ $record->id_pelanggan }}" readonly
                            class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm sm:text-sm cursor-not-allowed bg-transparent">
                    </div>
                </td>
                <td>
                    <div>
                        <label class="block text-sm font-medium ">Nama Pelanggan</label>
                        <input type="text" value="{{ $record->name_pelanggan }}" readonly
                            class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm sm:text-sm cursor-not-allowed bg-transparent">
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                   
                    <div>
                        <label class="block text-sm font-medium ">ODP</label>
                        <input type="text" value="{{ $record->odp_name }}" readonly
                            class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm sm:text-sm cursor-not-allowed bg-transparent">
                    </div>
                </td>
                <td>
                    <div>
                        <label class="block text-sm font-medium ">Port</label>
                        <input type="text" value="{{ $record->port_odp }}" readonly
                            class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm sm:text-sm cursor-not-allowed bg-transparent">
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div>
                        <label class="block text-sm font-medium ">Merk ONT</label>
                        <input type="text" value="{{ $record->merk_ont }}" readonly
                            class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm sm:text-sm cursor-not-allowed bg-transparent">
                    </div>
                </td>
                <td>

                    <div>
                        <label class="block text-sm font-medium ">SN ONT</label>
                        <input type="text" value="{{ $record->sn_ont }}" readonly
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
            <td>FOTO LABEL ID PELANGGAN DI ODP   
                <div x-data>
                    <button type="button" 
                        @click.prevent="
                            fetch('/api/rotate-photo', { 
                                method: 'POST', 
                                headers: {'Content-Type':'application/json'}, 
                                body: JSON.stringify({ path: '{{ $record->foto_label_id_plg }}' }) 
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

            <td>FOTO STIKER QR DI RUMAH PELANGGAN
                <div x-data>
                    <button type="button" 
                        @click.prevent="
                            fetch('/api/rotate-photo', { 
                                method: 'POST', 
                                headers: {'Content-Type':'application/json'}, 
                                body: JSON.stringify({ path: '{{ $record->foto_qr }}' }) 
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
                    @if ($record->foto_label_id_plg)
                        <img id="foto1" src="{{ asset('storage/'.$record->foto_label_id_plg) }}?v={{ now()->timestamp }}" style="width:420px;height:460px;object-fit:contain;">
   
                           
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
                    @if ($record->foto_qr)
                        <img id="foto2" src="{{ asset('storage/'.$record->foto_qr) }}?v={{ now()->timestamp }}" 
                        style="width:100%;height:100%;object-fit:contain;">
   
                        
                    @else
                        <span style="font-size:12px;color:#999">Tidak ada foto</span>
                    @endif
                </div>
            </td>
        </tr>
        <tr style="height:30px; font-weight:bold">
            <td>FOTO ODP
                <div x-data>
                    <button type="button" 
                        @click.prevent="
                            fetch('/api/rotate-photo', { 
                                method: 'POST', 
                                headers: {'Content-Type':'application/json'}, 
                                body: JSON.stringify({ path: '{{ $record->foto_label_odp }}' }) 
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
            <td>FOTO SN ONT
                <div x-data>
                    <button type="button" 
                        @click.prevent="
                            fetch('/api/rotate-photo', { 
                                method: 'POST', 
                                headers: {'Content-Type':'application/json'}, 
                                body: JSON.stringify({ path: '{{ $record->foto_sn_ont }}' }) 
                            })
                            .then(res => res.json())
                            .then(res => {
                                if(res.success){
                                    const img = document.getElementById('foto4');
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
                    @if ($record->foto_label_odp)
                        <img id="foto3" src="{{ asset('storage/'.$record->foto_label_odp) }}?v={{ now()->timestamp }}" 
                        style="width:100%;height:100%;object-fit:contain;">
                        
                    @else
                        <span style="font-size:12px;color:#999">Tidak ada foto</span>
                    @endif
                </div>
            </td>

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
                    @if ($record->foto_sn_ont)
                        <img id="foto4" src="{{ asset('storage/'.$record->foto_sn_ont) }}?v={{ now()->timestamp }}" 
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