<div 
    x-data="cameraCapture()" 
    x-init="initCamera()" 
    class="flex flex-col items-center space-y-3 p-4 rounded-lg shadow">

    <video x-ref="video" autoplay playsinline class="rounded-md border w-64 h-48 bg-black"></video>
    <canvas x-ref="canvas" class="hidden"></canvas>

    <div wire:ignore>
    <button 
        type="button" 
        @click="takePhoto" 
        class="fi-btn fi-btn-primary inline-flex items-center gap-2">
        <x-heroicon-o-camera class="w-5 h-5" />
        Ambil Foto
    </button>
</div>

    <template x-if="photoData">
        <img :src="photoData" alt="Preview" class="w-64 h-48 rounded-md border mt-2">
    </template>
</div>

<script>
    function cameraCapture() {
        return {
            stream: null,
            photoData: null,
            initCamera() {
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    navigator.mediaDevices.getUserMedia({ video: true })
                        .then(stream => {
                            this.stream = stream;
                            this.$refs.video.srcObject = stream;
                        })
                        .catch(err => {
                            alert('Gagal mengakses kamera: ' + err.message);
                        });
                } else {
                    alert('Browser tidak mendukung kamera');
                }
            },
            takePhoto() {
                const video = this.$refs.video;
                const canvas = this.$refs.canvas;
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                this.photoData = canvas.toDataURL('image/png');

                // Kirim ke form Filament
                this.$wire.set('data.check_in_evidence', this.photoData);
                this.$wire.set('data.check_out_evidence', this.photoData);
            }
        }
    }
</script>
