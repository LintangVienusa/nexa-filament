<div x-data="photoComponent()" x-init="init()">
    <video x-ref="video" autoplay playsinline class="w-64 h-48 border"></video>
    <canvas x-ref="canvas" class="hidden"></canvas>

    <button type="button" @click="takePhoto()">Ambil Foto</button>

    <template x-if="photo">
        <img :src="photo" class="w-64 h-48 border mt-2" />
    </template>

    <!-- simpan ke form state -->
    <input type="hidden" wire:model="{{ $getStatePath() ?? 'check_in_evidence' }}" x-ref="input" />

    <button type="button" wire:click="savePhotoDirectly">Simpan Foto</button>

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

                    // update hidden input supaya form state ikut terisi
                    this.$refs.input.value = this.photo;
                    this.$refs.input.dispatchEvent(new Event('input'));
                }
            }
        }
    </script>
</div>
