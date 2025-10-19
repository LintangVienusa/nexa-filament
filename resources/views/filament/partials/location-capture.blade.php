<div x-data="locationCapture('{{ $getId() }}')" x-init="initMap()" class="w-full h-64 rounded-lg border">
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
        office: [-6.244446527436399, 106.58591198909295], // kantor

        initMap() {
            const mapId = 'map-' + id;
            const container = document.getElementById(mapId);
            container.style.minHeight = '250px';

            this.map = L.map(mapId).setView(this.office, 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(this.map);

            // radius kantor
            L.circle(this.office, { color: 'red', fillColor: '#f03', fillOpacity: 0.3, radius: this.radius }).addTo(this.map);

            setTimeout(() => {
                this.map.invalidateSize();
                this.setMarker();
            }, 300);
        },

        setMarker() {
            // ambil nilai dari form Filament
            this.lat = parseFloat(document.getElementById('check_in_latitude')?.value) || null;
            this.lng = parseFloat(document.getElementById('check_in_longitude')?.value) || null;

            if (this.lat && this.lng) {
                if (this.marker) this.map.removeLayer(this.marker);
                this.marker = L.marker([this.lat, this.lng]).addTo(this.map);
                this.map.setView([this.lat, this.lng], 15);
            }
        }
    }
}
</script>
@endpush
@endonce
