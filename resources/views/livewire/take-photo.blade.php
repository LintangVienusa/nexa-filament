<div x-data="photoCapture()" class="space-y-2">
    <video x-ref="video" autoplay playsinline width="250" class="rounded border mb-2"></video>
    <canvas x-ref="canvas" class="hidden"></canvas>

    <div class="flex gap-2">
        <button type="button" @click="startCamera()" class="px-3 py-1 bg-blue-600 text-white rounded">Buka Kamera</button>
        <button type="button" @click="capturePhoto()" class="px-3 py-1 bg-green-600 text-white rounded">Ambil Foto</button>
        {{-- <button type="button" @click="savePhotoToLivewire()" class="px-3 py-1 bg-indigo-600 text-white rounded" x-show="photo">Simpan Foto</button> --}}
        <button type="button"
    x-on:click="$wire.savePhoto(photo)"
    class="px-3 py-1 bg-indigo-600 text-white rounded"
    x-show="photo">
    Simpan Foto
</button>
    </div>

    <div x-show="photo" class="flex justify-center mt-2">
        <img :src="photo" class="rounded-lg border" width="250">
    </div>

    <input type="hidden" name="check_in_evidence" />

    <script>
        window.addEventListener('photoTaken', event => {
            const input = document.querySelector('input[name="check_in_evidence"]');
            if(input) input.value = event.detail.path;
            
        });
        
       

        function photoCapture() {
            return {
                video: null,
                canvas: null,
                photo: null,
                startCamera() {
                    this.video = this.$refs.video;
                    this.canvas = this.$refs.canvas;
                    navigator.mediaDevices.getUserMedia({ video: true })
                        .then(stream => this.video.srcObject = stream)
                        .catch(e => alert('Gagal buka kamera: ' + e));
                },
                capturePhoto() {
                    const ctx = this.canvas.getContext('2d');
                    this.canvas.width = this.video.videoWidth;
                    this.canvas.height = this.video.videoHeight;
                    ctx.drawImage(this.video, 0, 0);
                    this.photo = this.canvas.toDataURL('image/jpg');
                    console.log('[JS] Photo captured:', this.photo);
                },
                savePhotoToLivewire() {
                    // Panggil method Livewire
                    Livewire.emit('savePhoto', this.photo);
                }
            }
        }

        // Tangkap emit Livewire
        Livewire.on('savePhoto', photoData => {
            Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'))
                .call('savePhoto', photoData);
        });

    </script>
</div>