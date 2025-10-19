<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        {{ filament()->getMeta() }}

        {{-- ✅ Tambahkan Leaflet CSS & JS --}}
        <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        />
        <script
            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            defer
        ></script>

        {{ filament()->getStyles() }}
        @vite('resources/css/app.css')
        {{ filament()->getScripts() }}
    </head>

    <body class="filament-body">
        {{ $slot }}
    </body>
</html>
