<div x-data="photoComponent()" x-init="init()">
    <video x-ref="video" autoplay playsinline class="w-64 h-48 border"></video>
    <canvas x-ref="canvas" class="hidden"></canvas>

    <div class="mt-2">
        <button type="button" @click="takePhoto()" class="px-3 py-1 bg-blue-600 text-white rounded">
            Ambil Foto
        </button>
    </div>

    <template x-if="photo">
        <img :src="photo" class="w-64 h-48 border mt-2" />
    </template>

    <script>
        function photoComponent() {
            return {
                photo: null,
                init() {
                    navigator.mediaDevices.getUserMedia({ video: true })
                        .then(stream => this.$refs.video.srcObject = stream)
                        .catch(err => console.error(err));
                },
                takePhoto() {
                    const canvas = this.$refs.canvas;
                    const video = this.$refs.video;
                    canvas.width = 640;
                    canvas.height = 480;
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                    this.photo = canvas.toDataURL('image/jpeg', 0.7);

                    Livewire.emit('photoTaken', this.photo);
                }
            }
        }
    </script>
</div>
