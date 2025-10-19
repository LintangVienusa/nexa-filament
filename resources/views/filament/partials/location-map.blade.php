<div 
    x-data="locationCapture('{{ $getId() }}')" 
    x-init="initMap()" 
    class="w-full h-64 rounded-lg border border-gray-300"
>
    <div id="map-{{ $getId() }}" class="w-full h-full rounded"></div>
</div>

@once
    @push('scripts')
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

        <script>
            function locationCapture(id) {
                return {
                    map: null,
                    marker: null,
                    lat: null,
                    lng: null,
                    radius: 500,
                    office: [-6.244446527436399, 106.58591198909295], // Koordinat kantor

                    initMap() {
                        const mapId = 'map-' + id;
                        const container = document.getElementById(mapId);
                        container.style.minHeight = '250px';

                        this.map = L.map(mapId).setView(this.office, 15);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                        }).addTo(this.map);

                        L.circle(this.office, {
                            color: 'red',
                            fillColor: '#f03',
                            fillOpacity: 0.3,
                            radius: this.radius
                        }).addTo(this.map);

                        setTimeout(() => {
                            this.map.invalidateSize();
                            this.getLocation();
                        }, 300);
                    },

                    getLocation() {
                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition((position) => {
                                this.lat = position.coords.latitude;
                                this.lng = position.coords.longitude;

                                if (this.marker) {
                                    this.map.removeLayer(this.marker);
                                }

                                this.marker = L.marker([this.lat, this.lng]).addTo(this.map);
                                this.map.setView([this.lat, this.lng], 15);

                                // Ambil field input check in & check out
                                const checkInLat = document.getElementById('check_in_latitude');
                                const checkInLng = document.getElementById('check_in_longitude');
                                const checkOutLat = document.getElementById('check_out_latitude');
                                const checkOutLng = document.getElementById('check_out_longitude');

                                // Jika belum check in → simpan ke check in
                                if (checkInLat && !checkInLat.value) {
                                    checkInLat.value = this.lat;
                                    checkInLat.dispatchEvent(new Event('input'));

                                    checkInLng.value = this.lng;
                                    checkInLng.dispatchEvent(new Event('input'));
                                } 
                                // Kalau sudah check in → simpan ke check out
                                else if (checkOutLat && !checkOutLat.value) {
                                    checkOutLat.value = this.lat;
                                    checkOutLat.dispatchEvent(new Event('input'));

                                    checkOutLng.value = this.lng;
                                    checkOutLng.dispatchEvent(new Event('input'));
                                }

                            }, () => {
                                alert('Gagal mengambil lokasi.');
                            });
                        } else {
                            alert('Browser tidak mendukung geolocation');
                        }
                    }
                }
            }
        </script>
    @endpush
@endonce
