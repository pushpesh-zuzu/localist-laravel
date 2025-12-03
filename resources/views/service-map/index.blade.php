@section('css')
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/leaflet.js"></script>

    <style>
        /* Map container */
        #map { width: 100%; height: 100vh; }

        /* Pin styles adapted from your example (valid CSS) */
        .pin-container { position: relative; width: 40px; height: 40px; display:inline-block; }

        .pin {
          width: 30px;
          height: 30px;
          border-radius: 50% 50% 50% 0;
          position: absolute;
          transform: rotate(-45deg);
          left: 50%;
          top: 50%;
          margin: -20px 0 0 -20px;
          animation-name: bounce;
          animation-fill-mode: both;
          animation-duration: 0.8s;
          display:flex;
          align-items:center;
          justify-content:center;
          font-size:13px;
          color: #fff;
          font-weight:600;
          box-shadow: 0 1px 4px rgba(0,0,0,0.25);
        }
        .pin:after {
          content: '';
          width: 14px;
          height: 14px;
          margin: 8px 0 0 8px;
          background: rgba(0,0,0,0.18);
          position: absolute;
          border-radius: 50%;
          z-index: 0;
        }

        .pin-green { background: #00C800; } /* green */
        .pin-blue  { background: #1370FF; } /* blue */
        .pin-red   { background: #FF3B30; } /* red */

        .pulse {
          background: rgba(0,0,0,0.08);
          border-radius: 50%;
          height: 14px;
          width: 14px;
          position: absolute;
          left: 50%;
          top: 50%;
          margin: 11px 0 0 -12px;
          transform: rotateX(55deg);
          z-index: -2;
        }
        .pulse:after {
          content: "";
          border-radius: 50%;
          height: 40px;
          width: 40px;
          position: absolute;
          margin: -13px 0 0 -13px;
          animation: pulsate 1s ease-out;
          animation-iteration-count: infinite;
          opacity: 0.0;
          box-shadow: 0 0 1px 2px rgba(137,132,155,0.25);
          animation-delay: 0s;
        }

        @keyframes pulsate {
          0%   { transform: scale(0.1, 0.1); opacity: 0.0; }
          50%  { opacity: 1.0; }
          100% { transform: scale(1.2, 1.2); opacity: 0; }
        }

        @keyframes bounce {
          0%   { opacity: 0; transform: translateY(-2000px) rotate(-45deg); }
          60%  { opacity: 1; transform: translateY(30px) rotate(-45deg); }
          80%  { transform: translateY(-10px) rotate(-45deg); }
          100% { transform: translateY(0) rotate(-45deg); }
        }

        /* Legend */
        .map-legend {
            background: rgba(255,255,255,0.95);
            padding: 8px 10px;
            border-radius: 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.3);
            font-family: Arial, sans-serif;
            font-size: 13px;
            line-height: 1.2;
        }
        .legend-item { display: flex; align-items: center; margin-bottom: 6px; }
        .legend-swatch {
            width: 18px; height: 18px; margin-right: 8px; border-radius: 50%;
            display:inline-block; border: 1px solid rgba(0,0,0,0.08);
        }

        /* small screens */
        @media (max-width: 576px) {
            .map-legend { font-size: 12px; padding: 6px 8px; }
        }
    </style>
@endsection

<x-app-layout>
    <x-slot name="header">Service Map</x-slot>

    <div class="card mb-4">
       <div class="card-body" style="padding: 0px !important; ">
            <div class="row">
                <div class="col-md-12">
                    <div id="map" style="height: 700px;"></div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // init map
    const map = L.map('map').setView([54.5, -3], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // legend
    const legend = L.control({ position: 'topright' });
    legend.onAdd = function () {
        const div = L.DomUtil.create('div', 'map-legend');
        div.innerHTML = `
            <div style="font-weight:600;margin-bottom:6px;">Map legend</div>
            <div class="legend-item"><span class="legend-swatch" style="background:#00C800"></span> Buyer with credit (green)</div>
            <div class="legend-item"><span class="legend-swatch" style="background:#FF3B30"></span> Buyer without credit (red)</div>
            <div class="legend-item"><span class="legend-swatch" style="background:#1370FF"></span> Lead (blue)</div>
        `;
        return div;
    };
    legend.addTo(map);

    // helpers
    function safeNum(v) {
        const n = parseFloat(v);
        return Number.isFinite(n) ? n : null;
    }
    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Keep created markers so we can clear
    const markers = [];
    function clearMarkers() {
        markers.forEach(m => {
            try { map.removeLayer(m); } catch(e){}
        });
        markers.length = 0;
    }

    let initialFit = false;

    // Create a DivIcon with count and color and an x-offset so three pins do not exactly overlap.
    // offsetX in pixels (-20, 0, +20)
    function makeCountDivIcon(count, colorClass, offsetX = 0) {
        const html = `
            <div class="pin-container" style="transform: translateX(${offsetX}px);">
                <div class="pin ${colorClass}" style="z-index:2;">
                    <span style="transform: rotate(45deg); display:inline-block; position:relative; z-index:3;">${count}</span>
                </div>
                <div class="pulse" style="z-index:1;"></div>
            </div>
        `;
        return L.divIcon({
            html: html,
            className: 'custom-count-icon', // keep default styling off
            iconSize: [40, 40],
            iconAnchor: [20, 40], // bottom center
            popupAnchor: [0, -38]
        });
    }

    // Main fetch + render
    async function refreshMap() {
        try {
            console.log('Refreshing map data...');
            const res = await fetch('{{ route("service-map.data") }}', { cache: 'no-store' });
            if (!res.ok) {
                console.warn('Map data fetch failed:', res.status);
                return;
            }
            const data = await res.json();

            clearMarkers();

            // aggregate by postcode + coordinates fallback
            // key = postcode|lat|lng to avoid collapsing different coordinates that share postcode string
            const agg = new Map();

            (data.buyers || []).forEach(b => {
                const lat = safeNum(b.latitude);
                const lng = safeNum(b.longitude);
                const postcode = (b.zipcode || b.postcode || '').trim();
                if (lat === null || lng === null) return;
                const key = `${postcode}|${lat.toFixed(6)}|${lng.toFixed(6)}`;
                if (!agg.has(key)) agg.set(key, {
                    postcode,
                    lat,
                    lng,
                    buyers_with_credit: 0,
                    buyers_no_credit: 0,
                    leads: 0
                });
                const rec = agg.get(key);
                const credit = Number(b.total_credit) || 0;
                if (credit > 0) rec.buyers_with_credit += 1;
                else rec.buyers_no_credit += 1;
            });

            (data.leads || []).forEach(l => {
                const lat = safeNum(l.latitude);
                const lng = safeNum(l.longitude);
                const postcode = (l.postcode || '').trim();
                if (lat === null || lng === null) return;
                const key = `${postcode}|${lat.toFixed(6)}|${lng.toFixed(6)}`;
                if (!agg.has(key)) agg.set(key, {
                    postcode,
                    lat,
                    lng,
                    buyers_with_credit: 0,
                    buyers_no_credit: 0,
                    leads: 0
                });
                const rec = agg.get(key);
                rec.leads += 1;
            });

            // create markers for each aggregated point
            const bounds = L.latLngBounds();
            let addedAny = false;

            for (const [key, rec] of agg.entries()) {
                const { lat, lng, postcode, buyers_with_credit, buyers_no_credit, leads } = rec;

                // We will create up to 3 separate markers (green, red, blue) if their count > 0
                // offset them slightly: green (-18px), blue (0), red (+18px) so labels readable
                if (buyers_with_credit > 0) {
                    const ico = makeCountDivIcon(buyers_with_credit, 'pin-green', -18);
                    const m = L.marker([lat, lng], { icon: ico })
                        .bindPopup(`
                            <div style="min-width:200px;">
                                <strong>Postcode:</strong> ${escapeHtml(postcode || '')}<br>
                                <strong>Buyers with credit:</strong> ${buyers_with_credit}<br>
                                <strong>Buyers without credit:</strong> ${buyers_no_credit}<br>
                                <strong>Leads:</strong> ${leads}
                            </div>
                        `);
                    m.addTo(map);
                    markers.push(m);
                    try { bounds.extend(m.getLatLng()); addedAny = true; } catch(e){}
                }

                if (leads > 0) {
                    const ico = makeCountDivIcon(leads, 'pin-blue', 0);
                    const m = L.marker([lat, lng], { icon: ico })
                        .bindPopup(`
                            <div style="min-width:200px;">
                                <strong>Postcode:</strong> ${escapeHtml(postcode || '')}<br>
                                <strong>Buyers with credit:</strong> ${buyers_with_credit}<br>
                                <strong>Buyers without credit:</strong> ${buyers_no_credit}<br>
                                <strong>Leads:</strong> ${leads}
                            </div>
                        `);
                    m.addTo(map);
                    markers.push(m);
                    try { bounds.extend(m.getLatLng()); addedAny = true; } catch(e){}
                }

                if (buyers_no_credit > 0) {
                    const ico = makeCountDivIcon(buyers_no_credit, 'pin-red', 18);
                    const m = L.marker([lat, lng], { icon: ico })
                        .bindPopup(`
                            <div style="min-width:200px;">
                                <strong>Postcode:</strong> ${escapeHtml(postcode || '')}<br>
                                <strong>Buyers with credit:</strong> ${buyers_with_credit}<br>
                                <strong>Buyers without credit:</strong> ${buyers_no_credit}<br>
                                <strong>Leads:</strong> ${leads}
                            </div>
                        `);
                    m.addTo(map);
                    markers.push(m);
                    try { bounds.extend(m.getLatLng()); addedAny = true; } catch(e){}
                }
            }

            // Fit to bounds on first load (or when map empty then keep view)
            if (!initialFit && addedAny && bounds.isValid()) {
                map.fitBounds(bounds.pad(0.18));
                initialFit = true;
            }

        } catch (err) {
            console.error('refreshMap error', err);
        }
    }

    // initial load
    refreshMap();

    // refresh interval (every 10s)
    // const REFRESH_MS = 10000;
    // setInterval(refreshMap, REFRESH_MS);
});
</script>
