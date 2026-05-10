import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet's default marker icon paths after Vite bundling.
// Without this, marker images resolve to /marker-icon.png (404).
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const startIcon = L.divIcon({
    className: 'route-map-marker route-map-marker--start',
    html: '<span aria-hidden="true">S</span>',
    iconSize: [24, 24],
    iconAnchor: [12, 12],
});

const endIcon = L.divIcon({
    className: 'route-map-marker route-map-marker--end',
    html: '<span aria-hidden="true">F</span>',
    iconSize: [24, 24],
    iconAnchor: [12, 12],
});

function readTileConfig(container) {
    const head = document.head;
    return {
        url:
            container.dataset.tileUrl ||
            head.querySelector('meta[name="map-tile-url"]')?.content ||
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution:
            container.dataset.tileAttribution ||
            head.querySelector('meta[name="map-tile-attribution"]')?.content ||
            '&copy; OpenStreetMap contributors',
        maxZoom:
            parseInt(container.dataset.tileMaxZoom, 10) ||
            parseInt(
                head.querySelector('meta[name="map-tile-max-zoom"]')?.content,
                10,
            ) ||
            19,
    };
}

function readGeoJson(container) {
    const payload = container.querySelector('script[type="application/json"]');
    if (!payload) {
        return null;
    }
    try {
        return JSON.parse(payload.textContent);
    } catch (error) {
        console.error('route-map: invalid GeoJSON payload', error);
        return null;
    }
}

function initMap(container) {
    if (container.dataset.mapInitialized === '1') {
        return;
    }

    const geojson = readGeoJson(container);
    if (!geojson) {
        return;
    }

    const tile = readTileConfig(container);

    const map = L.map(container, {
        scrollWheelZoom: false,
    });

    L.tileLayer(tile.url, {
        attribution: tile.attribution,
        maxZoom: tile.maxZoom,
    }).addTo(map);

    const trackLayer = L.geoJSON(geojson, {
        style: {
            color: '#dc2626',
            weight: 4,
            opacity: 0.85,
        },
    }).addTo(map);

    const coords =
        geojson.geometry?.coordinates ||
        geojson.features?.[0]?.geometry?.coordinates ||
        [];

    if (coords.length >= 1) {
        const [startLng, startLat] = coords[0];
        const [endLng, endLat] = coords[coords.length - 1];

        L.marker([startLat, startLng], { icon: startIcon, alt: 'Start' }).addTo(
            map,
        );
        L.marker([endLat, endLng], { icon: endIcon, alt: 'Finish' }).addTo(map);
    }

    map.fitBounds(trackLayer.getBounds(), { padding: [24, 24] });

    container.addEventListener('click', () => map.scrollWheelZoom.enable(), {
        once: true,
    });

    container.dataset.mapInitialized = '1';
}

function initAll(root = document) {
    root.querySelectorAll('[data-route-map]').forEach(initMap);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initAll());
} else {
    initAll();
}

document.addEventListener('livewire:navigated', () => initAll());
document.addEventListener('livewire:update', () => initAll());

export { initMap, initAll };
